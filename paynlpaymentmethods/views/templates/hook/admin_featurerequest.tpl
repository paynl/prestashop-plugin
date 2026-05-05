<div class="panel" id="feature_request">
    <div class="panel-heading">
        <i class="icon-envelope"></i> {l s='Suggestions?' mod='paynlpaymentmethods'}
    </div>  
    <div class="panel">
        <p>{l s='If you have a feature request or other ideas, let us know!' mod='paynlpaymentmethods'}</p>
        <p>{l s='Your submission will be reviewed by our development team.' mod='paynlpaymentmethods'}</p>
        <p>{l s='If needed, we will contact you for further information via the e-mail address provided.' mod='paynlpaymentmethods'}</p>
        <p>{l s='Please note: this form is not for Support requests, please contact support@pay.nl for this.' mod='paynlpaymentmethods'}</p>
    </div>
    <br/>
    <input type="hidden" id="csrf" value="{$paynl_ajax_csrf_token}">
    <div class="panel">
    <form>   
        <input type="hidden" id="pay-ajaxurl" value="{$ajaxURL}">      
        <div class="form-group">
            <span id="email_error" class="FR_Error">Please fill in a valid email.</span>
            <label class="control-label col-lg-1 align-right">{l s='Email (optional)' mod='paynlpaymentmethods'}</label>
            <div class="col-lg-11">
                <input type="text" name="FR_Email" id="FR_Email">       
                <p class="help-block"></p>      
            </div>
        </div>     
        <div class="clearboth"></div>  
        <div class="form-group">
            <span id="message_error" class="FR_Error">Please fill in a message.</span>
            <label class="control-label col-lg-1 align-right required">{l s='Message' mod='paynlpaymentmethods'}</label>
            <div class="col-lg-11">
                <textarea id="FR_Message" name="FR_Message" placeholder="{l s='Leave your suggestions here...' mod='paynlpaymentmethods'}"></textarea>
                <p class="help-block"></p>              
            </div>
        </div>    
    </form>     
    <div class="clearboth"></div>  
    </div>   
    <div class="panel-footer" id="FR_Footer">        
        <button type="submit" value="1" id="module_form_submit_btn_fr" name="btnSubmitFR"
                class="btn btn-default pull-right">
            <i class="process-icon-mail"></i> {l s='Send'}
        </button>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="FR_Success_Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">    
      <div class="modal-body">
        {l s='Sent! Thank you for your contribution.' mod='paynlpaymentmethods'}
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="FR_fail_Modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">    
      <div class="modal-body">
        {l s='Email could not be sent, please try again later.' mod='paynlpaymentmethods'}
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>