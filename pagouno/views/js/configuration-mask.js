jQuery(document).ready(() => {
    setTimeout(()=>{
        new Cleave( document.querySelector('#PAGOUNO_C3'), {
            numeral: true,
            delimiter: ',',
            numeralPositiveOnly: true,
            numeralDecimalScale: 4,
            numeralThousandsGroupStyle: 'none'
        });
        new Cleave( document.querySelector('#PAGOUNO_C6'), {
            numeral: true,
            delimiter: ',',
            numeralPositiveOnly: true,
            numeralDecimalScale: 4,
            numeralThousandsGroupStyle: 'none'
        });
        new Cleave( document.querySelector('#PAGOUNO_C9'), {
            numeral: true,
            delimiter: ',',
            numeralPositiveOnly: true,
            numeralDecimalScale: 4,
            numeralThousandsGroupStyle: 'none'
        });
        new Cleave( document.querySelector('#PAGOUNO_C12'), {
            numeral: true,
            delimiter: ',',
            numeralPositiveOnly: true,
            numeralDecimalScale: 4,
            numeralThousandsGroupStyle: 'none'
        });
        new Cleave( document.querySelector('#PAGOUNO_C24'), {
            numeral: true,
            delimiter: ',',
            numeralPositiveOnly: true,
            numeralDecimalScale: 4,
            numeralThousandsGroupStyle: 'none'
        });
        new Cleave( document.querySelector('#PAGOUNO_CA3'), {
            numeral: true,
            delimiter: ',',
            numeralPositiveOnly: true,
            numeralDecimalScale: 4,
            numeralThousandsGroupStyle: 'none'
        });
        new Cleave( document.querySelector('#PAGOUNO_CA6'), {
            numeral: true,
            delimiter: ',',
            numeralPositiveOnly: true,
            numeralDecimalScale: 4,
            numeralThousandsGroupStyle: 'none'
        });
        new Cleave( document.querySelector('#PAGOUNO_CA12'), {
            numeral: true,
            delimiter: ',',
            numeralPositiveOnly: true,
            numeralDecimalScale: 4,
            numeralThousandsGroupStyle: 'none'
        });
        new Cleave( document.querySelector('#PAGOUNO_CA18'), {
            numeral: true,
            delimiter: ',',
            numeralPositiveOnly: true,
            numeralDecimalScale: 4,
            numeralThousandsGroupStyle: 'none'
        });
    }, 1000)
})