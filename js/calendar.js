$=jQuery.noConflict();

$(document).ready(function () {

    $("#datepicker1").datepicker(
            {
                dateFormat: 'yy-mm-dd',
                startDate: '1900-01-01',
                endDate: '2100-12-31'
            })
            .on('changeDate', function (e) {
                // Revalidate the date field
                $('#dateRangeForm').formValidation('revalidateField', 'date');
            });

    $("#datepicker2").datepicker(
            {
                dateFormat: 'yy-mm-dd',
                startDate: '1900-01-01',
                endDate: '2100-12-31'
            })
            .on('changeDate', function (e) {
                // Revalidate the date field
                $('#dateRangeForm').formValidation('revalidateField', 'date');
            });
});
