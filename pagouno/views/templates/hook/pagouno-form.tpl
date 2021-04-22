<form method="post" action="{$action}">
    <div id="form-pagouno">
        <div class="pu-form-row">
            <div class="pu-input pu-form-first">
                <label>Número de tarjeta <span id="pu_cc_type"></span><span class="pu-required"> *<span id="puCcError" style="display: none;"> Incorrecto</span></span></label>
                <input class="pagoUno-input" id="pagoUno_ccNo" type="text" autocomplete="off">
            </div>
            <div class="pu-input pu-form-last">
                <label>Fecha de Vto. <span class="pu-required">*<span id="puEpError" style="display: none;"> Incorrecto</span></span></label>
                <input class="pagoUno-input" id="pagoUno_expdate" type="text" autocomplete="off">
            </div>
        </div>
        <div class="pu-form-row">
            <div class="pu-input pu-form-first">
                <label>Nombre del Titular <span class="pu-required">* <span id="puNameError" style="display: none;"> Incorrecto</span></span></label>
                <input class="pagoUno-input" id="pagoUno_ccName" type="text" autocomplete="off">
            </div>
            <div class="pu-input pu-form-last">
                <label>Cod. de Seguridad <span class="pu-required">*<span id="puCvcError" style="display: none;"> Incorrecto</span></span></label>
                <input class="pagoUno-input" id="pagoUno_cvc" type="text" autocomplete="off">
            </div>
        </div>
        <div class="pu-form-row">
            <div class="pu-input pu-form-first">
            <label>Tipo de Documento <span class="pu-required">*</span></label>
            <select class="pagoUno-select" id="pagoUno_ccDocType" class="" name="select" style="width: 100%">
                <option class="pu-option" value="DNI" selected>DNI</option>
                <option class="pu-option" value="CUIL">CUIL</option>
                <option class="pu-option" value="OTRO">OTRO</option>
            </select>
            </div>
                <div class="pu-input pu-form-last">
                <label>Número <span class="pu-required">*<span id="puDocNumError" style="display: none;"> Incorrecto</span></span></label>
                <input class="pagoUno-input" id="pagoUno_ccDocNum" type="text" autocomplete="off">
            </div>
        </div>
        <div class="pu-form-row">
            <div class="pu-input pu-form-wide">
                <label>Cantidad de Cuotas <span class="pu-required">*</span></label>
                <select class="pagoUno-select" id="pagoUno_dues" class="" name="select" style="width: 100%">
                    <option value="no">1 solo pago de ${$payment_total}</option>
                    {foreach from=$payment_options key=key item=option}
                        <option value="{$key}">{$option.option.inner}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    </div>
    <input id="pagouno_cart_id" name="pagouno_cart_id" type="hidden" value="{$id_cart}">
    <input id="pagouno_token" name="pagouno_token" type="hidden" value="">
    <input id="pagouno_cuotas" name="pagouno_cuotas" type="hidden" value="">
    <input id="pagouno_total" name="pagouno_total" type="hidden" value="">
</form>