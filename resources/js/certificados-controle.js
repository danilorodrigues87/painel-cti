(function(){
	const debounceTimers = {};

	function urlControle(){
		if(typeof controleCertificadoUrl !== 'undefined' && controleCertificadoUrl){
			return url_base + controleCertificadoUrl;
		}
		return url_base + 'painel/certificados/controle';
	}

	function lerLinha($el){
		const $tr = $el.closest('tr');
		const id = parseInt($el.data('id') || $tr.data('cert-id'), 10);
		if(!id) return null;
		const impresso = $tr.find('.cert-ctrl-impresso').is(':checked') ? 1 : 0;
		const dataRetirada = ($tr.find('.cert-ctrl-retirada').val() || '').trim();
		return { id: id, impresso: impresso, data_retirada: dataRetirada };
	}

	function atualizarBadgeRetirada($tr, dataRetirada){
		const $badge = $tr.find('.cert-badge-retirada');
		if(!$badge.length) return;
		if(dataRetirada){
			$badge.removeClass('bg-secondary').addClass('bg-success').text('Retirado');
		}else{
			$badge.removeClass('bg-success').addClass('bg-secondary').text('Pendente');
		}
	}

	function salvarControle(payload, $tr){
		$.post(urlControle(), payload, function(res){
			if(typeof res === 'string'){
				try { res = JSON.parse(res); } catch(e) { res = null; }
			}
			if(!res || !res.success){
				const msg = (res && res.message) ? res.message : 'Não foi possível salvar o controle.';
				if(typeof Swal !== 'undefined'){
					Swal.fire({ title: 'Erro', text: msg, icon: 'error', timer: 2500, showConfirmButton: false });
				}
				if($tr && $tr.length){
					$tr.addClass('table-danger');
					setTimeout(function(){ $tr.removeClass('table-danger'); }, 2000);
				}
				return;
			}
			if($tr && $tr.length){
				atualizarBadgeRetirada($tr, res.data_retirada || '');
				$tr.removeClass('table-danger');
			}
		}, 'json').fail(function(){
			if(typeof Swal !== 'undefined'){
				Swal.fire({ title: 'Erro', text: 'Falha ao salvar.', icon: 'error', timer: 2000, showConfirmButton: false });
			}
		});
	}

	function agendarSalvar($el){
		const dados = lerLinha($el);
		if(!dados) return;
		const key = String(dados.id);
		clearTimeout(debounceTimers[key]);
		debounceTimers[key] = setTimeout(function(){
			const $tr = $el.closest('tr');
			salvarControle(dados, $tr);
		}, 350);
	}

	$(document).on('change', '.cert-ctrl-impresso', function(){
		const $el = $(this);
		const dados = lerLinha($el);
		if(!dados) return;
		clearTimeout(debounceTimers[String(dados.id)]);
		salvarControle(dados, $el.closest('tr'));
	});

	$(document).on('change', '.cert-ctrl-retirada', function(){
		agendarSalvar($(this));
	});
})();
