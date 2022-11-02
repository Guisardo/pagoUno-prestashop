function isValidExpiresDate(month, year) {
    var currentTime, expiry, prefix;

    if (!(month && year)) {
        return false;
    }

    month = parseInt(month, 10);

    if (!(month && month <= 12)) {
        return false;
    }

    if (year.length === 2) {
        prefix = new Date().getFullYear();
        prefix = prefix.toString().slice(0, 2);
        year = prefix + year;
    }

    expiry = new Date(year, month);

    currentTime = new Date();

    expiry.setMonth(expiry.getMonth() - 1);
    expiry.setMonth(expiry.getMonth() + 1, 1);

    return expiry > currentTime;
}
function isNaranja(v) {
    const reg = /^589562[0-9]{0,}$/;
    return reg.test(v);
}
function isValidLuhn(value) {
    if (/[^0-9-\s]+/.test(value)) return false;

    let nCheck = 0, bEven = false;

    value = value.replace(/\D/g, "");

    for (var n = value.length - 1; n >= 0; n--) {
        var cDigit = value.charAt(n),
        nDigit = parseInt(cDigit, 10);

        if (bEven && (nDigit *= 2) > 9) nDigit -= 9;

        nCheck += nDigit;
        bEven = !bEven;
    }

    return (nCheck % 10) === 0;
}
function isNaranjaValidLuhn(value) {
    if (/[^0-9-\s]+/.test(value)) return false;

    value = value.replace(/\D/g, "");

    const nDigitList = value.split("").map(function(d) {
        return parseInt(d);
    });

    if (nDigitList.length > 15) {
        const R = (nDigitList[0] * 4) + (nDigitList[1] * 3) + (nDigitList[2] * 2) + (nDigitList[3] * 7) + (nDigitList[4] * 6)
            + (nDigitList[5] * 5) + (nDigitList[6] * 4) + (nDigitList[7] * 3) + (nDigitList[8] * 2) + (nDigitList[9] * 7)
            + (nDigitList[10] * 6) + (nDigitList[11] * 5) + (nDigitList[12] * 4) + (nDigitList[13] * 3) + (nDigitList[14] * 2);

        let X16 = 11 - (R % 11);

        if (X16 > 9) {
            X16 = 0;
        }

        return nDigitList[15] === X16;
    } else {
        return false;
    }
}
jQuery(document).ready(() => {
    prestashop.on('changedCheckoutStep', function () {
        if ( document.querySelector('#form-pagouno') !== null ) {
            jQuery.extend(Cleave.CreditCardDetector.blocks, {
                cabal: [4, 4, 4, 4],
                naranja: [4, 4, 4, 4],
            });
        
            jQuery.extend(Cleave.CreditCardDetector.re, {
                cabal: /^589657[0-9]{0,}$/,
                naranja: /^589562[0-9]{0,}$/,
            });
        
            let creditCardNumber = document.querySelector('#pagoUno_ccNo');
            let creditCardSecurityCode = document.querySelector('#pagoUno_cvc');
            let creditCardExpires = document.querySelector('#pagoUno_expdate');
            let creditCardDocNumber = document.querySelector('#pagoUno_ccDocNum');
            let creditCardName = document.querySelector('#pagoUno_ccName');
    
            var cCreditCard = new Cleave(creditCardNumber, {
                creditCard: true,
                creditCardStrictMode: false
            });
    
            var cVerification = new Cleave(creditCardSecurityCode, {
                blocks: [4],
                numericOnly: true
            });
            var cExpires = new Cleave(creditCardExpires, {
                date: true,
                datePattern: ['m', 'y']
            });
            var cDocNumber= new Cleave(creditCardDocNumber, {
                numeral: true,
                numeralThousandsGroupStyle: 'none'
            });
    
    
            creditCardNumber.addEventListener('input', function (e) {
                var rawValue = cCreditCard.getRawValue();
                var target = e.target;
    
                if (!isNaranja(rawValue)) {
                    if (isValidLuhn(rawValue) && rawValue.length) {
                        jQuery(target).removeClass('pu-invalid');
                    } else {
                        jQuery(target).addClass('pu-invalid');
                    }
                } else {
                    if (isNaranjaValidLuhn(rawValue) && rawValue.length) {
                        jQuery(target).removeClass('pu-invalid');
                    } else {
                        jQuery(target).addClass('pu-invalid');
                    }
                }
            });
    
            creditCardSecurityCode.addEventListener('input', function (e) {
                var rawValue = cVerification.getRawValue();
                var target = e.target;
                if (rawValue.length) {
                    jQuery(target).removeClass('pu-invalid');
                } else {
                    jQuery(target).addClass('pu-invalid');
                }
            });
    
            creditCardExpires.addEventListener('input', function (e) {
                var rawValue = cExpires.getRawValue();
                var target = e.target;
                let isFutureDate = false;
                if (rawValue.length === 4) {
                    isFutureDate = isValidExpiresDate(rawValue[0] + rawValue[1] , rawValue[2] + rawValue[3]);
                } else {
                    isFutureDate = false;
                }
                if (rawValue.length && isFutureDate) {
                    jQuery(target).removeClass('pu-invalid');
                } else {
                    jQuery(target).addClass('pu-invalid');
                }
            });
    
            creditCardName.addEventListener('input', function (e) {
                jQuery('#pagoUno_ccName').removeClass('pu-invalid');
            });
    
            creditCardDocNumber.addEventListener('input', function (e) {
                var rawValue = cDocNumber.getRawValue();
                var target = e.target;
                if (rawValue.length) {
                    jQuery(target).removeClass('pu-invalid');
                } else {
                    jQuery(target).addClass('pu-invalid');
                }
            });
        
            if (php_params.extendedForm == 1) {
                var birthDate = document.querySelector('#pagoUno_birthDate');
                var phoneNumber = document.querySelector('#pagoUno_phone');
                var country = document.querySelector('#pagoUno_country');
                var state = document.querySelector('#pagoUno_state');
                var city = document.querySelector('#pagoUno_city');
                var street = document.querySelector('#pagoUno_street');
                var streetNumber = document.querySelector('#pagoUno_streetNumber');
                var email = document.querySelector('#pagoUno_email');
    
                var cBirthDate = new Cleave(birthDate, {
                    date: true,
                });
    
                var cPhone = new Cleave(phoneNumber, {
                    numeral: true,
                    numeralThousandsGroupStyle: 'none'
                });
    
                birthDate.addEventListener('input', function (e) {
                    var rawValue = cBirthDate.getRawValue();
                    var target = e.target;
                    if (rawValue.length) {
                        jQuery(target).removeClass('pu-invalid');
                    } else {
                        jQuery(target).addClass('pu-invalid');
                    }
                });
    
                email.addEventListener('input', function (e) {
                    jQuery('#pagoUno_email').removeClass('pu-invalid');
                });
    
                country.addEventListener('input', function (e) {
                    jQuery('#pagoUno_country').removeClass('pu-invalid');
                });
    
                state.addEventListener('input', function (e) {
                    jQuery('#pagoUno_state').removeClass('pu-invalid');
                });
    
                city.addEventListener('input', function (e) {
                    jQuery('#pagoUno_city').removeClass('pu-invalid');
                });
    
                street.addEventListener('input', function (e) {
                    jQuery('#pagoUno_street').removeClass('pu-invalid');
                });
    
                streetNumber.addEventListener('input', function (e) {
                    jQuery('#pagoUno_streetNumber').removeClass('pu-invalid');
                });
            }
        }
    })
});