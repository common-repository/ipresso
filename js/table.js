
$ = jQuery.noConflict();
$(document).ready(function () {
    $('#table_id1').DataTable(
            {
               "language": {
                   
        "paginate": {
        "sFirst":    "Pierwsza",
        "sPrevious": "Poprzednia",
        "sNext":     "Następna",
        "sLast":     "Ostatnia"
                     },
            "lengthMenu": "Wyświetl _MENU_ rekordów na stronę",
            "zeroRecords": "Brak wyników",
            "info": "Wyświetlono _PAGE_ z _PAGES_",
            "infoEmpty": "Brak dostępnych rekordów",
            "search": "Szukaj:",
            "infoFiltered": "(suma przeszukanych rekordów _MAX_)"

        }
            });
    $('#table_id2').DataTable();
});