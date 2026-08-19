(function () {
	'use strict';

	var pollMs = 30000;
	var pollTimer = null;
	var aberto = false;

	function baseUrl() {
		return (typeof URL !== 'undefined' && URL) ? URL : '';
	}

	function postNotif(data, cb) {
		$.ajax({
			url: baseUrl() + '/painel/notificacoes',
			method: 'POST',
			data: data,
			dataType: 'json'
		}).done(function (res) {
			if (typeof cb === 'function') cb(res || {});
		}).fail(function () {
			if (typeof cb === 'function') cb({ success: false });
		});
	}

	function esc(s) {
		return $('<div/>').text(s == null ? '' : String(s)).html();
	}

	function fmtTempo(iso) {
		if (!iso) return '';
		var d = new Date(String(iso).replace(' ', 'T'));
		if (isNaN(d.getTime())) return iso;
		var now = new Date();
		var diff = Math.floor((now - d) / 1000);
		if (diff < 60) return 'agora';
		if (diff < 3600) return Math.floor(diff / 60) + ' min';
		if (diff < 86400) return Math.floor(diff / 3600) + ' h';
		return d.toLocaleDateString('pt-BR');
	}

	function renderBadge(n) {
		var $b = $('#staff-notif-badge');
		n = parseInt(n, 10) || 0;
		if (n <= 0) {
			$b.addClass('d-none').text('0');
			return;
		}
		$b.removeClass('d-none').text(n > 99 ? '99+' : String(n));
	}

	function renderLista(itens, sqlOk, msg) {
		var $box = $('#staff-notif-lista').empty();
		if (!sqlOk) {
			$box.append('<div class="px-3 py-3 text-warning small">' + esc(msg || 'Execute database/staff_notificacoes.sql') + '</div>');
			return;
		}
		if (!itens || !itens.length) {
			$box.append('<div class="px-3 py-3 text-muted small">Nenhuma notificação recente.</div>');
			return;
		}
		itens.forEach(function (n) {
			var lida = !!n.lida;
			var href = n.link ? (n.link.indexOf('http') === 0 ? n.link : baseUrl() + n.link) : '#';
			var $a = $('<a href="#" class="staff-notif-item dropdown-item py-2 px-3 border-bottom"></a>');
			$a.attr('data-id', n.id);
			if (!lida) $a.addClass('staff-notif-unread');
			$a.html(
				'<div class="d-flex gap-2">'
				+ '<div class="pt-1"><i class="' + esc(n.tipo_icon || 'fas fa-bell') + '"></i></div>'
				+ '<div class="flex-grow-1 min-w-0">'
				+ '<div class="fw-semibold small text-truncate">' + esc(n.titulo) + '</div>'
				+ '<div class="text-muted small text-truncate">' + esc(n.mensagem) + '</div>'
				+ '<div class="text-muted" style="font-size:11px;">' + esc(fmtTempo(n.created_at)) + '</div>'
				+ '</div></div>'
			);
			$a.on('click', function (e) {
				e.preventDefault();
				var id = parseInt($(this).data('id'), 10);
				postNotif({ acao: 'marcar_lida', id: id }, function () {
					if (n.link) window.location.href = href;
					else carregar(true);
				});
			});
			$box.append($('<div></div>').append($a));
		});
	}

	function carregar(silent) {
		postNotif({ acao: 'listar' }, function (res) {
			if (!res || !res.success) {
				if (!silent) renderLista([], false, 'Indisponível');
				return;
			}
			if (res.habilitado === false) {
				$('#nav-notif-wrap').addClass('d-none');
				return;
			}
			$('#nav-notif-wrap').removeClass('d-none');
			renderBadge(res.nao_lidas);
			if (aberto || !silent) {
				renderLista(res.itens || [], res.sql_ok !== false, res.message);
			}
		});
	}

	function contagem() {
		postNotif({ acao: 'contagem' }, function (res) {
			if (res && res.success && res.habilitado !== false) {
				$('#nav-notif-wrap').removeClass('d-none');
				renderBadge(res.nao_lidas);
			}
		});
	}

	$(function () {
		carregar(true);

		pollTimer = setInterval(function () {
			if (aberto) {
				carregar(true);
			} else {
				contagem();
			}
		}, pollMs);

		$('#navNotificacoes').on('show.bs.dropdown', function () {
			aberto = true;
			carregar(false);
		}).on('hide.bs.dropdown', function () {
			aberto = false;
		});

		$('#staff-notif-marcar-todas').on('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			postNotif({ acao: 'marcar_todas' }, function () {
				carregar(false);
			});
		});
	});
})();
