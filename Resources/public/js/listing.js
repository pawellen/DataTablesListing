/**
 * Created by pawel on 22.07.14.
 */

var _listing = (function() {

    var ajaxSearchDelay = 450;
    var typingTimer;
    $.extend($.fn.dataTable.defaults, {
        language: {
            processing: '<div id="ico_loader"><i class="icon-2x icon-spinner icon-spin"></i><div>',
            lengthMenu: 'Pokaż _MENU_ pozycji',
            zeroRecord: 'Zmodyfikuj kryteria wyszukiwania, aby zobaczyć wyniki',
            emptyTable: 'Zmodyfikuj kryteria wyszukiwania, aby zobaczyć wyniki',
            info: 'Pozycje od _START_ do _END_ z _TOTAL_ łącznie',
            infoEmpty: 'Pozycji 0 z 0 dostępnych',
            infoFiltered: '(filtrowanie spośród _MAX_ dostępnych pozycji)',
            infoPostFix: '',
            search: 'Szukaj:',
            paginate: {
                first: 'Pierwsza',
                previous: 'Poprzednia',
                next: 'Następna',
                last: 'Ostatnia'
            }
        }
    });

    var reDrawTableOnFilterInputEvent = function(elementh, table) {

        var _refreshTable = function () {
            table._fnDraw();
        };
        if (elementh.type === 'text') {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(_refreshTable, ajaxSearchDelay);
        } else {
            _refreshTable();
        }
    }


    var initTable = function(tableId, filtersId, settings) {
        var $table = $('#' + tableId);
        var $filters = $('#' + filtersId);
        var ajax_url = $table.data('ajaxsource');
        var defaultSettings = {
            processing: true,   // turn on processing loader display when data is loaded/processed
            serverSide: true,   // turn on ajax source
            searching: false,   // display build-in search box
            ajax: {             // ajax options
                url: ajax_url,
                data: function (data) {
                    // Process ajax data before send request to server:
                    $filters.find('input, select').each(function (index, input) {
                        var res = getInputNameAndValue(input);
                        if (res.value !== null) {
                            data[res.name] = res.value;
                        }
                    });
                    return data;
                },
                timeout: 15000,
                error: function(xhr, textStatus, error) {
                    if (xhr.status === 401) {
                        // Reload current window if response status is 401 (Unauthorized), event fired in AjaxAuthenticationListener when user session is expired
                        window.location.reload();
                    }
                }
            },
            drawCallback: function(settings) {
                // Process html after re-draw table:
                if (Math.ceil((this.fnSettings().fnRecordsDisplay()) / this.fnSettings()._iDisplayLength) > 1)  {
                    $('#' + tableId + '_paginate').show();
                } else {
                    $('#' + tableId + '_paginate').hide();
                }
            },
            /*
            rowCallback: function(row, data) {
                // Process single <tr>, after table row is added to DOM:
                for (var i = 0; i < data.length; i++) {
                    var td = $(':eq(' + i + ')', row);
                    td.replaceWith('-');
                }

                return row;
            },
            */
            //pagingType: "scrolling',
            pageLength: 20
        };


        // Init table:
        settings = $.extend(defaultSettings, settings);
        console.log('DataTables settings:', settings);
        var table = $table.dataTable(settings);

        // Start searching events:
        $filters.find('input').on('keyup', function() {
            reDrawTableOnFilterInputEvent(this, table);
        });
        $filters.find('select, input[type=\'checkbox\']').on('change', function() {
            reDrawTableOnFilterInputEvent(this, table);
        });
        $filters.find('.datepicker').on('dp.change', function() {
            reDrawTableOnFilterInputEvent(this, table);
        });

        return table;
    }


    var getInputNameAndValue = function(input) {
        var getLastChunkOfInputName = function(input_name)
        {
            var chunks = [];
            var full_name = input_name + '';
            var parts = full_name.split('[');
            for (var i in parts) {
                var part = parts[i];
                if (part.substr(-1) === ']') {
                    part = part.substr(0, part.length - 1);
                    chunks.push(part);
                }
            }

            return chunks.pop();
        }

        var value = null;
        var item = $(input);
        var type = item.prop('type');
        var name = '_filter[' + getLastChunkOfInputName(item.attr('name')) + ']';
        switch (input.nodeName.toLowerCase()) {
            case 'select':
                if (item.prop('multiple')) {
                    if (item.val() instanceof Array && item.val().length > 0) {
                        value = item.val();
                    }
                    name = '_filter[' + item.attr('name').replace('[]', '') + ']';
                } else {
                    if (item.val())
                        value = item.val();
                }
                break;

            case 'input':
                if ((type === 'checkbox' || type === 'radio') && item.prop('checked')) {
                    value = item.val();
                } else if (item.val() !== '') {
                    value = item.val() + '';
                }
                break;

            default:
                throw new Error('Unknown DOM node tagName "' + input.nodeName.toLowerCase() + '"');
        }

        return {
            name: name,
            value: value
        }
    }


    return {
        initTable: initTable
    }
})();