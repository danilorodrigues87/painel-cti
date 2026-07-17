/**
 * Inicia atendimento no inbox WhatsApp a partir de aluno / responsável / lead.
 * @param {string} telefone
 * @param {string} [nome]
 */
function iniciarAtendimentoWa(telefone, nome){
	const tel = String(telefone || '').trim();
	if(!tel){
		Swal.fire('Atenção', 'Este cadastro não possui WhatsApp.', 'warning');
		return;
	}
	Swal.fire({
		title: 'Abrindo atendimento...',
		allowOutsideClick: false,
		didOpen: function(){ Swal.showLoading(); }
	});
	$.post(url_base + 'painel/whatsapp', {
		acao: 'iniciar_atendimento',
		telefone: tel,
		nome: nome || ''
	}, function(res){
		if(!res || !res.success){
			Swal.fire('Erro', (res && res.message) || 'Não foi possível iniciar o atendimento.', 'error');
			return;
		}
		Swal.close();
		const url = res.redirect || (url_base + 'painel/whatsapp?conversa=' + res.conversa_id);
		window.open(url, '_blank');
	}, 'json').fail(function(){
		Swal.fire('Erro', 'Falha ao iniciar atendimento WhatsApp.', 'error');
	});
}
