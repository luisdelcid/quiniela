(function($){
  function msg($form, text, ok){ $form.find('.qm2026-message').text(text).toggleClass('is-error', !ok); }
  $(document).on('submit','.qm2026-join-form',function(e){
    e.preventDefault(); var $f=$(this); msg($f,'Guardando...',true);
    $.post(QM2026.ajaxUrl, $f.serialize()+'&action=qm2026_join_pool&nonce='+QM2026.nonce).done(function(r){ if(r.success){ msg($f,r.data.message,true); window.location.reload(); } else msg($f,r.data||'Error',false); }).fail(function(xhr){ msg($f,(xhr.responseJSON&&xhr.responseJSON.data)||'Error',false); });
  });
  $(document).on('submit','.qm2026-prediction-form',function(e){
    e.preventDefault(); var $f=$(this); if($f.hasClass('is-locked')) return; msg($f,'Guardando...',true);
    $.post(QM2026.ajaxUrl, $f.serialize()+'&action=qm2026_save_prediction&nonce='+QM2026.nonce+'&match_id='+$f.data('match-id')).done(function(r){ msg($f,r.success?r.data.message:(r.data||'Error'),!!r.success); }).fail(function(xhr){ msg($f,(xhr.responseJSON&&xhr.responseJSON.data)||'Error',false); });
  });
})(jQuery);
