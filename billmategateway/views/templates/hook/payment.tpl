{*
* Created by PhpStorm.
* User: jesper
* Date: 15-03-17
* Time: 13:01
* @author Jesper Johansson jesper@boxedlogistics.se
* @copyright Billmate AB 2015
*}
<style>
    #spanMessage .billmate-loader {
        background: url("{$smarty.const._MODULE_DIR_}billmategateway/views/img/ajax-loader.gif") 15px 15px no-repeat #fbfbfb;
        z-index: 10000;
        height: 100px;
        width: 100px;
        margin-left: 45%
    }

    @media screen and (max-width: 768px) {
        #facebox img{
            width: 90%!important;
        }
        #facebox{
            width: 80%!important;
            right:10%!important;
            left: 10%!important;
        }
    }

    #divFrameParent * {
        text-align: center!important;
        font-size: 1em;
        font-family: tahoma!important;
    }

    #divFrameParent .checkout-heading {
        color: #000000!important;
        font-weight: bold!important;
        font-size: 13px!important;
        margin-bottom: 15px!important;
        padding: 8px!important;
    }
    #divFrameParent .button:hover{
        background:#0B6187!important;
    }
    #divFrameParent .button {
        background-color: #1DA9E7!important;
        background: #1DA9E7!important;
        border: 0 none!important;
        border-radius: 8px!important;
        box-shadow: 2px 2px 2px 1px #EAEAEA!important;
        color: #FFFFFF!important;
        cursor: pointer!important;
        font-family: arial!important;
        font-size: 14px!important;
        font-weight: bold!important;
        padding: 3px 17px!important;
    }
    {if $template == 'new'}
    div.payment_module {
        border: 1px solid #d6d4d4;
        background: #fbfbfb;
        margin-bottom: 10px;
        -moz-border-radius: 4px;
        -webkit-border-radius: 4px;
        border-radius: 4px;
    }
    div.payment_module a {
        display: block;
        /*border: 1px solid #d6d4d4;*/
        -moz-border-radius: 4px;
        -webkit-border-radius: 4px;
        border-radius: 4px;
        font-size: 17px;
        line-height: 23px;
        color: #333;
        font-weight: bold;
        padding: 33px 40px 34px 170px;
        letter-spacing: -1px;
        position: relative;

    }
    {else}
    div.payment_module {
        margin: 0;
    }
    div.payment_module a {
        display:block;
    }
    {/if}
</style>
{foreach $methods as $method}
    <div class="row">
        <div class="col-xs-12">
            <div class="payment_module">
    <style>
        div.payment_module a.{$method.type} {
            background: url("{$smarty.const._MODULE_DIR_}{$method.icon}") 15px 15px no-repeat #fbfbfb;

        }
        div.payment_module a.{$method.type}:after{
            display: block;
            content: "\f054";
            position: absolute;
            right: 15px;
            margin-top: -11px;
            top: 50%;
            font-family: "FontAwesome";
            font-size: 25px;
            height: 22px;
            width: 14px;
            color: #777;
        }
        div.payment_module a.{$method.type}:hover,
        div.payment_module a.{$method.type}:visited,
        div.payment_module a.{$method.type}:active{
            text-decoration: none;
        }
        div.payment_module .error{
            clear:both;
        }
        #terms,#terms-partpay{
            cursor: pointer!important;
            font-size: inherit;
            display: inherit;
            border: none;
            padding: inherit;
            text-decoration: underline;
        }
    </style>
    {if $method.type != 'billmateinvoice' && $method.type != 'billmatepartpay' && $method.type != 'billmateinvoiceservice'}
        <a {if $template == 'new'} class="{$method.type}"{/if} href="{$method.controller}" id="{$method.type}">{if $template == 'legacy'}<img src="{$smarty.const._MODULE_DIR_}{$method.icon}"/>{/if}{$method.name|escape:'html'}</a>
    {else}
        <a {if $template == 'new'} class="{$method.type}"{/if} href="{$method.controller|escape:'url'}" id="{$method.type|escape:'html'}">{if $template == 'legacy'}<img src="{$smarty.const._MODULE_DIR_}{$method.icon}"/>{/if}{$method.name|escape:'html'} {if $method.type == 'billmatepartpay'} {l s='from' mod='billmategateway'} {displayPrice|regex_replace:'/[.,]0+/':'' price=$method.monthly_cost.monthlycost} {l s='/ month' mod='billmategateway'} {elseif $method.invoiceFee.fee > 0} - {l s='Invoice fee' mod='billmategateway'} {displayPrice|regex_replace:'/[.,]0+/':'' price=$method.invoiceFee.fee_incl_tax}  {l s='is added to the order sum' mod='billmategateway'}{/if}
        </a>
        <div style="display:none;" id="{$method.type}-fields" class="payment-form">
            <form action="javascript://" class="{$method.type|escape:'html'}">
                <div style="" id="error_{$method.type}"></div>
                {if $method.type == 'billmatepartpay'}
                    <div class="accountcontainer">
                        <label style="display:block; padding:10px; {if $template == 'legacy'}clear:both;{/if}">{l s='Payment options:' mod='billmategateway'}</label>
                        <select name="paymentAccount" style="margin-left:10px;">
                            {foreach $method.pClasses as $pclass}
                                <option value="{$pclass.paymentplanid}">{$pclass.description} {displayPrice price=$pclass.monthlycost}
                                    / {l s='month' mod='billmategateway'}</option>
                            {/foreach}
                        </select>
                    </div>
                {/if}
                <div class="pno_container" style="padding:10px">
                    <label for="pno_{$method.type|escape:'html'}" style="display:block; {if $template == 'legacy'}clear:both;{/if}">{l s='Social Security Number / Corporate Registration Number:' mod='billmategateway'}</label>
                    <input id="pno_{$method.type|escape:'html'}" name="pno_{$method.type|escape:'html'}" type="text"/>
                </div>
                <div class="agreements" style="padding:10px">
                    <div style="float:left;">
                    <input type="checkbox" checked="checked" id="agree_with_terms_{$method.type|escape:'html'}"
                           name="agree_with_terms_{$method.type|escape:'html'}"/>
                    </div>
                    <label for="terms_{$method.type|escape:'html'}" style="float:left; max-width: 80%;">{$method.agreements|escape:'quotes'}</label>
                </div>
                <div style="padding:10px; padding-top:0px;"><button type="submit" style="margin-bottom:10px;" class="btn btn-default button button-medium pull-right" id="{$method.type|escape:'html'}Submit" value=""><span>{l s='Proceed' mod='billmategateway'}</span></button></div>
                <div style="clear:both;"></div>
            </form>
        </div>
    {/if}
    </div>
        </div>
    </div>
{/foreach}
<script type="text/javascript" src="{$smarty.const._MODULE_DIR_}billmategateway/views/js/billmatepopup.js"></script>
<script type="text/javascript">
    function getPayment(method){
        if(typeof submitAccount == 'function')
            submitAccount();
        $.ajax({
            url: ajaxurl,
            data: 'method='+method,
            success: function(response){
                var result = JSON.parse(response);
                if(result.success){
                    location.href = result.redirect;
                } else {
                    alert(result.content);
                }
            }
        })
        return false;
    }
    if(!billmatepopupLoaded){
        var script = document.createElement('script');
        script.setAttribute('src','{$smarty.const._MODULE_DIR_}billmategateway/views/js/billmatepopup.js');
        script.setAttribute('type','text/javascript');
        document.getElementsByTagName('head')[0].appendChild(script);
    }
    function addTerms(){
        jQuery(document).Terms('villkor',{ldelim}invoicefee:0{rdelim}, '#terms');
        jQuery(document).Terms('villkor_delbetalning',{ldelim}eid: PARTPAYMENT_EID, effectiverate:34{rdelim},'#terms-partpay');
    }
    if(!$.fn.Terms){
        jQuery.getScript('https://billmate.se/billmate/base_jquery.js',function(){ldelim}addTerms(){rdelim});
    }
    var version = "{$ps_version|escape:'html'}"
    var PARTPAYMENT_EID = "{$eid}";
    var ajaxurl = "{$link->getModuleLink('billmategateway', 'billmateapi', ['ajax'=> 0], true)}";

    var emptypersonerror = "{l s='PNO/SSN missing' mod='billmategateway'}";
    var checkbox_required = "{l s='Please check the checkbox for confirm this e-mail address is correct and can be used for invoicing.' mod='billmategateway'}";
    var carrierurl;
    {if $opc|default:false }
    carrierurl = "{$link->getPageLink("order-opc", true)}";
    {else}
    carrierurl = "{$link->getPageLink("order", true)}";
    {/if}
    var loadingWindowTitle = '{l s='Processing....' mod='billmategateway'}';

    var windowtitlebillmate = "{l s='Pay by invoice can be made only to the address listed in the National Register. Would you make the purchase with address:' mod='billmategateway'}";
    jQuery(document.body).on('click', '#billmate_button', function () {
        var method = $(this).data('method');
        if ($('form.billmate'+method).length > 1)
            var form = $('form.realbillmate'+method).serializeArray();
        else
            var form = $('form.billmate' + method).serializeArray();
        modalWin.HideModalPopUp();

        if(!billmateprocessing) {

            getData('&geturl=yes', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, method);
        }
    });
    if($('#pno').length){
        $('.pno_container').hide();
        if($('#pno_billmatepartpay')){
            $('#pno').on('change',function(e){
                $('#pno_billmatepartpay').val(e.target.value);
            });
        }
        if($('#pno_billmateinvoice')){
            $('#pno').on('change',function(e){
                $('#pno_billmateinvoice').val(e.target.value);
            });
        }
        if($('#pno_billmateinvoiceservice')){
            $('#pno').on('change',function(e){
                $('#pno_billmateinvoiceservice').val(e.target.value);
            });
        }

    }
    $('#billmatecardpay').click(function(e) {
        e.preventDefault();
        getPayment('cardpay');
        return false;
    });
    $('#billmatebankpay').click(function(e) {
        e.preventDefault();
        getPayment('bankpay');
        return false;
    });
    $('#billmateinvoice').click(function (e) {
        $('a#billmateinvoice').css('padding-bottom','10px');
        $('a#billmatepartpay').css('padding-bottom','34px');
        $('#billmatepartpay-fields').hide();
        $('#billmateinvoiceservice-fields').hide();
        $('#billmateinvoice-fields').show();
        if ($('#pno').length > 0) {
            $('#pno_billmateinvoice').val($('#pno').val());
            $('#billmateinvoice-fields .pno_container').hide();
        }
        e.preventDefault();
    })

    $('#billmateinvoiceservice').click(function (e) {
        $('a#billmateinvoiceservice').css('padding-bottom','10px');
        $('a#billmatepartpay').css('padding-bottom','34px');
        $('#billmatepartpay-fields').hide();
        $('#billmateinvoice-fields').hide()
        $('#billmateinvoiceservice-fields').show();
        if ($('#pno').length > 0) {
            $('#pno_billmateinvoiceservice').val($('#pno').val());
            $('#billmateinvoiceservice-fields .pno_container').hide();

        }
        e.preventDefault();
    })
    $('#billmatepartpay').click(function (e) {
        $('a#billmateinvoice').css('padding-bottom','34px');
        $('a#billmatepartpay').css('padding-bottom','10px');
        $('#billmateinvoice-fields').hide();
        $('#billmateinvoiceservice-fields').hide();
        $('#billmatepartpay-fields').show();
        if ($('#pno').length > 0) {
            $('#pno_billmatepartpay').val($('#pno').val());
            $('#billmatepartpay-fields .pno_container').hide();
        }
        e.preventDefault();
    })
    $('#billmateinvoiceSubmit').click(function (e) {
        if ($('#pno').length > 0) {
            $("#pno_billmateinvoice").val($('#pno').val());
        }
        if($('form.billmateinvoice').length > 1) {
            var form = $('form.realbillmateinvoice').serializeArray();
        } else {
            var form = $('form.billmateinvoice').serializeArray();
        }
        if ($.trim($('#pno_billmateinvoice').val()) == '') {
            alert(emptypersonerror);
            if($checkoutButton)
                $checkoutButton.disabled = false;
            return;
        }
        if ($('#agree_with_terms_billmateinvoice').prop('checked') == true) {
            var data = '';
            if($('#invoice_address').prop('checked') == true)
                    data = '&invoice_address=true';
            if(!billmateprocessing)
                getData(data, form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, 'invoice');
        } else {
            alert($('<textarea/>').html(checkbox_required).text());
        }
        e.preventDefault();
    })
    $('#billmateinvoiceserviceSubmit').click(function (e) {
        if($('form.billmateinvoiceservice').length > 1) {
            var form = $('form.realbillmateinvoiceservice').serializeArray();
        } else {
            var form = $('form.billmateinvoiceservice').serializeArray();
        }
        if ($.trim($('#pno_billmateinvoiceservice').val()) == '') {
            alert(emptypersonerror);
            if($checkoutButton)
                $checkoutButton.disabled = false;
            return;
        }
        if ($('#agree_with_terms_billmateinvoiceservice').prop('checked') == true) {
            if(!billmateprocessing)
                getData('', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, 'invoiceservice');
        } else {
            alert($('<textarea/>').html(checkbox_required).text());
        }
        e.preventDefault();
    })

    $('#billmatepartpaySubmit').click(function (e) {
        if ($('#pno').length > 0) {
            $("#pno_billmatepartpay").val($('#pno').val());
        }
        if($('form.billmatepartpay').length > 1){
            var form = $('form.realbillmatepartpay').serializeArray();
        } else {
            var form = $('form.billmatepartpay').serializeArray();
        }
        if ($.trim($('#pno_billmatepartpay').val()) == '') {
            alert(emptypersonerror);
            if($checkoutButton)
                $checkoutButton.disabled = false;
            return;
        }
        if ($('#agree_with_terms_billmatepartpay').prop('checked') == true) {
            if(!billmateprocessing)
                getData('', form, version, ajaxurl, carrierurl, loadingWindowTitle, windowtitlebillmate, 'partpay');
        } else {
            alert($('<textarea/>').html(checkbox_required).text());
        }
        e.preventDefault();

    })
    if($('input[name="id_payment_method"]').length) {
        $(document).on('click', 'input[name="id_payment_method"]', function (element) {

            $('.payment-form').hide();
            var value = element.target.value;

            if ($('#payment_' + value).parents('.item,.alternate_item').hasClass('fields')) {


                $('#payment_' + value).parents('.item,.alternate_item').children('.payment_description').children('.payment-form').show();
                var method = $('#payment_' + value).parents('.item,.alternate_item').children('.payment_description').children('.payment-form').attr('id');
                var methodName = method.replace('-fields', '');
                if ($('#pno').length > 0) {
                    $('#pno_' + methodName).val($('#pno').val());
                }
                $('.confirm_button')[$('.confirm_button').length - 1].onclick = function (e) {

                    submitAccount($('#' + methodName + 'Submit'));


                    e.preventDefault()
                }

            } else if ($('#' + value).parent('.payment_module').children('.payment-form')) {
                var el = $('#' + value).parent('.payment_module').children('.payment-form');
                var method = el.attr('id');

                if (typeof method != 'undefined') {
                    var methodName = method.replace('-fields', '');

                    if (!$('#payment_' + value).parents('.item,.alternate_item').hasClass('fields'))
                        $('#payment_' + value).parents('.item,.alternate_item').addClass('fields');

                    $('#' + value).parent('.payment_module').children('.payment-form').appendTo($('.cssback.' + methodName).parents('.item,.alternate_item').children('.payment_description'));
                    $('.cssback.' + methodName).parents('.item,.alternate_item').children('.payment_description').children('.payment-form').children('.' + methodName).addClass('real' + methodName);
                    $('#' + value).parent('.payment_module').children('.payment-form').remove(methodName);
                    $('#' + method).show();
                    if ($('#pno').length > 0) {
                        $('#pno_' + methodName).val($('#pno').val());
                    }
                    $('#' + methodName + 'Submit').hide();
                    $checkoutbtn = $('.confirm_button')[$('.confirm_button').length - 1].onclick;
                    $('.confirm_button')[$('.confirm_button').length - 1].onclick = function (e) {
                        submitAccount($('#' + methodName + 'Submit'));

                        e.preventDefault();
                    }
                } else {
                    if ($checkoutbtn != null) {
                        $('.confirm_button')[$('.confirm_button').length - 1].onclick = $checkoutbtn
                    }
                }
            }

        });
    }

</script>
