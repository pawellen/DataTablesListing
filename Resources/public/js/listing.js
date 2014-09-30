/**
 * Created by pawel on 22.07.14.
 */

var _listing = function() {

    var ajaxSearchDelay = 450;
    var typingTimer;
    $.extend($.fn.dataTable.defaults, {
        "language": {
            "processing": '<div id="ico_loader"><i class="icon-2x icon-spinner icon-spin"></i><div>',
            "lengthMenu": "Pokaż _MENU_ pozycji",
            "zeroRecords": "Zmodyfikuj kryteria wyszukiwania, aby zobaczyć wyniki",
            "emptyTable": "Zmodyfikuj kryteria wyszukiwania, aby zobaczyć wyniki",
            "info": "Pozycje od _START_ do _END_ z _TOTAL_ łącznie",
            "infoEmpty": "Pozycji 0 z 0 dostępnych",
            "infoFiltered": "(filtrowanie spośród _MAX_ dostępnych pozycji)",
            "infoPostFix": "",
            "search": "Szukaj:",
            "paginate": {
                "first": "Pierwsza",
                "previous": "Poprzednia",
                "next": "Następna",
                "last": "Ostatnia"
            }
        }
    });

    var getLastChunkOfInputName = function(input_name)
    {
        var chunks = [];
        var full_name = input_name + '';
        var parts = full_name.split('[');
        for (var i in parts) {
            var part = parts[i];
            if (part.substr(-1) == ']') {
                part = part.substr(0, part.length - 1);
                chunks.push(part);
            }
        }

        return chunks.pop();
    }

    var pushInputItemsToAjaxData = function(input, aoData) {
        var value = null;
        var item = $(input);
        var type = item.prop('type');
        var name = 'filter[' + getLastChunkOfInputName(item.attr('name')) + ']';
        switch (input.nodeName.toLowerCase()) {

            case 'select':
                if (item.prop('multiple')) {
                    if (item.val() instanceof Array && item.val().length > 0) {
                        value = item.val();
                    }
                    name = 'filter[' + item.attr('name').replace('[]', '') + ']';
                } else {
                    if (item.val() != '' && item.val() != null)
                        value = item.val();
                }
                break;

            case 'input':
                if (type == 'text' && item.val() !== '') {
                    value = item.val() + '';
                } else if (type == 'checkbox' && item.prop('checked')) {
                    value = item.val();
                } else if (type == 'radio' && item.prop('checked')) {
                    value = item.val();
                }
                break;

            default:
                throw new Error('Unknown DOM node tagName "' + input.nodeName.toLowerCase() + '"');
        }

        if (value !== null) {
            aoData.push({name: name, value: value});
        }
    }


    var reDrawTableOnFilterInputEvent = function(elementh, table) {

        var _refreshTable = function () {
            table._fnDraw();
        };
        if (elementh.type == 'text') {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(_refreshTable, ajaxSearchDelay);
        } else {
            _refreshTable();
        }
    }


    var initTable = function(tableId, filtersId, initialJson) {

        var $table = $('#' + tableId);
        var $filters = $('#' + filtersId);
        var settings = {
            processing: true,
            serverSide: true,
            filter: false,
            sAjaxSource: $table.data('ajaxsource'),
            fnServerParams: function (aoData) {
                $filters.find('input, select').each(function (index, input) {
                    pushInputItemsToAjaxData(input, aoData);
                });
            },
            fnDrawCallback: function() {
                if (Math.ceil((this.fnSettings().fnRecordsDisplay()) / this.fnSettings()._iDisplayLength) > 1)  {
                    $('#' + tableId + '_paginate').show();
                } else {
                    $('#' + tableId + '_paginate').hide();
                }
            }
            /*
            fnInitComplete: function(oSettings, initialJson) {
                if (typeof initialJson === 'object')
                alert( 'DataTables has finished its initialisation.' );
            },
            data: 'dataSet',
            columns: (typeof initialData === 'object') ? initialData : []
            */

        };
        if (typeof extendedSetting === 'object') {
            settings = $.extend(settings, extendedSetting);
        }

        //console.log(settings);
        var table = $table.dataTable(settings);

        // Start searching events:
        //console.log($filters);
        $filters.find("input").on('keyup', function() {
            reDrawTableOnFilterInputEvent(this, table);
        });
        $filters.find("select, input[type='checkbox']").on('change', function() {
            reDrawTableOnFilterInputEvent(this, table);
        });
        $filters.find(".datepicker").on('dp.change', function() {
            reDrawTableOnFilterInputEvent(this, table);
        });

        return table;
    }

    return {
        initTable: initTable
    }
}();