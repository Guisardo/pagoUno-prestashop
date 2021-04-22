jQuery(function() {

    function isValidEmail(email) {
        return /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(email);
    }

    function successCallback (data) {
        //guardo el token en el form de woocommerce
        jQuery('#pagouno_token').val(data.data[0].id);
        //guardo la cantidad de cuotas
        let dues_val = jQuery('#pagoUno_dues').val();
        jQuery('#pagouno_cuotas').val(dues_val);
        //obtengo el form de prestashop
        let checkout_form = jQuery('#form-pagouno').parent();
        // desactivo token request
        checkout_form.off('submit');
        // hago submit del form
        checkout_form.submit();
        // listener para volver a llamar a tokenRequest en caso de no pasar la validacion
        checkout_form.on('submit', function(e) {
            e.preventDefault();
            tokenRequest();
        });
    };

    function errorCallback (data) {
        jQuery('#pagouno_token').val("-1");
        let checkout_form = jQuery('#form-pagouno').parent();
        // desactivo token request
        checkout_form.off('submit');
        // hago submit del form
        checkout_form.submit();
        // al a hacer submit vuelve a llamar a tokenRequest
        checkout_form.on('submit', function(e) {
            e.preventDefault();
            tokenRequest();
        });
    };

    function tokenRequest(e) {

        let inputs = document.querySelectorAll('input');
        let checkId = '';

        inputs.forEach(input => {
            let dataName = input.getAttribute('data-module-name');
            if(dataName === 'pagoUno') {
                checkId = input.getAttribute('id');
            }
        });

        if(jQuery('#' + checkId).is(':checked')) {
            let checkout_form = jQuery('#form-pagouno').parent();

            let validator = true;

            // validacion para los campos -----------------------------------------------------------------------------
            let ccNum = jQuery('#pagoUno_ccNo').val();
            let ccExpDate = jQuery('#pagoUno_expdate').val();
            let cvc = jQuery('#pagoUno_cvc').val();
            let name = jQuery('#pagoUno_ccName').val();
            let docNum = jQuery('#pagoUno_ccDocNum').val();

            let email = "";
            let bDate = "";
            let country = "";
            let state = "";
            let city = "";
            let street = "";
            let streetNumber = "";

            // validacion y formateo del numero de tarjeta

            if (ccNum.length > 0) {
                ccNum = ccNum.replace(/ /g, '');
                if (ccNum.length !== 16) {
                    validator = false;
                    jQuery('#pagoUno_ccNo').addClass('pu-invalid');
                } else {
                    if( jQuery('#pagoUno_ccNo').hasClass( "pu-invalid" ) ){
                        validator = false;
                    }
                }
            } else {
                validator = false;
                jQuery('#pagoUno_ccNo').addClass('pu-invalid');
            }

            // validacion y formateo de la fecha de vencimiento de la tarjeta
            if (ccExpDate.length > 0) {
                ccExpDate = ccExpDate.replace(/\//g, '');
                if(ccExpDate.length === 4){
                    if (jQuery('#pagoUno_expdate').hasClass( "pu-invalid" )){
                        validator = false;
                    } else {
                        ccExpDate = ccExpDate[2] + ccExpDate[3] + ccExpDate[0] + ccExpDate[1];
                    }
                } else {
                    validator = false;
                    jQuery('#pagoUno_expdate').addClass('pu-invalid');
                }
            } else {
                validator = false;
                jQuery('#pagoUno_expdate').addClass('pu-invalid');
            }

            // validacion y formateo del cvc
            if (cvc.length > 0) {
                if (cvc.length !== 3) {
                    validator = false;
                    jQuery('#pagoUno_cvc').addClass('pu-invalid');
                } else {
                    if (jQuery('#pagoUno_cvc').hasClass( 'pu-invalid' )) {
                        validator = false;
                    }
                }
            } else {
                validator = false;
                jQuery('#pagoUno_cvc').addClass('pu-invalid');
            }

            // validacion del nombre de usuario
            if (name.length > 0) {
                var patt = new RegExp("[0-9]", "g");
                if( patt.test( name )){
                    validator = false;
                    jQuery('#pagoUno_ccName').addClass('pu-invalid');
                }
            } else {
                validator = false;
                jQuery('#pagoUno_ccName').addClass('pu-invalid');
            }

            // validacion para el numero de documento
            if (docNum.length > 0) {
                if( jQuery('#pagoUno_ccDocNum').hasClass( "pu-invalid" ) ){
                    validator = false;
                } else {
                    docNum = docNum.replace(/\./g, '');
                }
            } else {
                validator = false;
                jQuery('#pagoUno_ccDocNum').addClass( "pu-invalid" );
            }

            // validacion para los campos adicionales ----------------------------------------------------------------
            if (php_params.extendedForm === 1) {

                bDate = jQuery('#pagoUno_birthDate').val();
                country = jQuery('#pagoUno_country').val();
                state = jQuery('#pagoUno_state').val();
                city = jQuery('#pagoUno_city').val();
                street = jQuery('#pagoUno_street').val();
                streetNumber = jQuery('#pagoUno_streetNumber').val();
                email = jQuery('#pagoUno_email').val();

                // validacion para el email
                if(email.length > 0 ){
                    email = email.toLowerCase();
                    if (!isValidEmail(email)) {
                        validator = false;
                        jQuery('#pagoUno_email').addClass('pu-invalid');
                    }
                } else {
                    validator = false;
                    jQuery('#pagoUno_email').addClass('pu-invalid');
                }

                // validacion para la fecha de nacimiento
                if (bDate.length > 0) {
                    bDate = bDate.replace(/\//g, '');
                    if( bDate.length !== 8){
                        validator = false;
                        jQuery('#pagoUno_birthDate').addClass('pu-invalid');
                    } else {
                        if (jQuery('#pagoUno_birthDate').hasClass('pu-invalid')) {
                            validator = false;
                        }
                    }
                } else {
                    validator = false;
                    jQuery('#pagoUno_birthDate').addClass('pu-invalid');
                }

                // validacion para el pais
                if(country.length > 0){
                    var patt = new RegExp("[0-9]", "g");
                    if( patt.test( country )){
                        validator = false;
                        jQuery('#pagoUno_country').addClass('pu-invalid');
                    }
                } else {
                    validator = false;
                    jQuery('#pagoUno_country').addClass('pu-invalid');
                }

                // validacion para la provincia
                if(state.length > 0){
                    var patt = new RegExp("[0-9]", "g");
                    if( patt.test( state )){
                        validator = false;
                        jQuery('#pagoUno_state').addClass('pu-invalid');
                    }
                } else {
                    validator = false;
                    jQuery('#pagoUno_state').addClass('pu-invalid');
                }

                // validacion para la ciudad
                if(city.length > 0){} else {
                    validator = false;
                    jQuery('#pagoUno_city').addClass('pu-invalid');
                }

                // validacion para la calle
                if(street.length > 0){} else {
                    validator = false;
                    jQuery('#pagoUno_street').addClass('pu-invalid');
                }

                // validacion para la altura
                if(streetNumber.length > 0){} else {
                    validator = false;
                    jQuery('#pagoUno_streetNumber').addClass('pu-invalid');
                }
            }

            if (validator) {

                jQuery('#puCcError').css("display", "none");
                jQuery('#puEpError').css("display", "none");
                jQuery('#puCvcError').css("display", "none");
                jQuery('#puNameError').css("display", "none");
                jQuery('#puDocNumError').css("display", "none");
                jQuery('#puEmailError').css("display", "none");

                if (php_params.extendedForm === 1) {
                    jQuery('#puBirthDateError').css("display", "none");
                    jQuery('#puCountryError').css("display", "none");
                    jQuery('#puStateError').css("display", "none");
                    jQuery('#puCityError').css("display", "none");
                    jQuery('#puStreetError').css("display", "none");
                    jQuery('#puStreetNumberError').css("display", "none");
                };

                function hasContent(val) {
                    let data = "";
                    if(val === undefined) {
                        return data;
                    } else {
                        data = val;
                        return data;
                    }
                }

                let pagounoForm = {
                    ccNo: ccNum,
                    ccExpDate: ccExpDate,
                    cvc: cvc,
                    name: name,
                    phone: jQuery('#pagoUno_phone').val(),
                    email: email,
                    bDate: bDate,
                    address: {
                        country: country,
                        state: state,
                        city: city,
                        street: street,
                        door_number: streetNumber
                    },
                    identification: {
                        document_type: jQuery('#pagoUno_ccDocType').val(),
                        document_number: docNum
                    }
                }

                let body = [];
                //obtengo los valores para el body
                if (php_params.extendedForm === 0) {
                    body[0] = {
                        primary_account_number: pagounoForm.ccNo,
                        expiration_date: pagounoForm.ccExpDate,
                        card_security_code: pagounoForm.cvc,
                        card_holder: {
                            first_name: "",
                            last_name: "",
                            front_name: pagounoForm.name,
                            telephone: "",
                            email: pagounoForm.email,
                            birth_date: "",
                            address: {
                                country: "",
                                state: "",
                                city: "",
                                street: "",
                                door_number: ""
                            },
                            identification: {
                                document_type: pagounoForm.identification.document_type,
                                document_number: pagounoForm.identification.document_number
                            }
                        }
                    }
                } else {
                    body[0] = {
                        primary_account_number: pagounoForm.ccNo,
                        expiration_date: pagounoForm.ccExpDate,
                        card_security_code: pagounoForm.cvc,
                        card_holder: {
                            first_name: "",
                            last_name: "",
                            front_name: pagounoForm.name,
                            telephone: pagounoForm.phone,
                            email: pagounoForm.email,
                            birth_date: pagounoForm.bDate,
                            address: {
                                country: pagounoForm.address.country,
                                state: pagounoForm.address.state,
                                city: pagounoForm.address.city,
                                street: pagounoForm.address.street,
                                door_number: pagounoForm.address.door_number
                            },
                            identification: {
                                document_type: pagounoForm.identification.document_type,
                                document_number: pagounoForm.identification.document_number
                            }
                        }
                    }
                }
                $.ajax({
                    method: "POST",
                    url: "https://api.pagouno.com/v1/Transaction/token",
                    data: JSON.stringify(body),
                    contentType: 'application/json',
                    dataType: 'json',
                    headers: {
                        Authorization: php_params.publickey,
                        'x-api-fp': ''
                    }
                }).done(function( response ) {
                    if(response.status === 200){
                        successCallback (response);
                    } else {
                        errorCallback (response);
                    }
                });
            } else {
                checkout_form.off('submit');
                checkout_form.on('submit', function(e) {
                    e.preventDefault();
                    tokenRequest();
                });
            }
        }
    }

    jQuery(function() {
        setTimeout(function(){
            const urlParams = new URLSearchParams(window.location.search);
            const puerror = urlParams.get('puerror');
            switch (puerror) {
                case null: break;
                case '1': alert('Su pago a sido rechazado.'); $("h1.step-title").after("<p>PAGO RECHAZADO, INTENTELO NUEVAMENTE</p>"); break;
                case '2': alert('Error de conexion, intentelo mas tarde.'); $("h1.step-title").last().after("<p>ERROR DE CONEXIÓN, NO PUDO REALIZARSE EL PAGO, INTENTELO NUEVAMENTE</p>"); break;
                case '400': alert('error 400: bad request'); break;
                case '403': alert('error 403: not authorized'); break;
                default: break;
            }
        }, 1000)
        if (document.querySelector('#form-pagouno') !== null) {
            let checkout_form = jQuery('#form-pagouno').parent();
            checkout_form.on('submit', function(e) {
                e.preventDefault();
                tokenRequest();
            });
        }
    });

});