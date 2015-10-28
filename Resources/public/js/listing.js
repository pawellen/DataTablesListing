/**
 * Created by pawel on 22.07.14.
 */

(function(self) {

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
            table.draw();
        };
        if (elementh.type === 'text') {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(_refreshTable, ajaxSearchDelay);
        } else {
            _refreshTable();
        }
    };


    self.initTable = function(tableId, filtersId, settings) {
        var $table = $('#' + tableId);
        var $filters = $('#' + filtersId);
        var url = $table.data('ajaxsource');
        var defaultSettings = {
            processing: true,   // turn on processing loader display when data is loaded/processed
            serverSide: true,   // turn on ajax source
            searching: false,   // display build-in search box
            ajax: {             // ajax options
                url: url,
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
            createdRow: function(row, data, dataIndex) {
                if (typeof data._isAjax !== 'undefined' && data._isAjax) {
                    // Process single <tr> for ajax call, after table row is added to add custom td html:
                    $('>', row).each(function(index, originalTd) {
                        var $td = $(data[index]);
                        var $originalTd = $(originalTd);
                        // Copy all attributes:
                        $.each($td.prop('attributes'), function() {
                            $originalTd.attr(this.name, this.value);
                        });
                        $originalTd.html($td.html());
                    });

                    // Add custom attributes:
                    if (typeof data._rowAttr === 'object') {
                        var $row = $(row);
                        for (var name in data._rowAttr) {
                            if (data._rowAttr.hasOwnProperty(name)) {
                                $row.attr(name, data._rowAttr[name]);
                            }
                        }
                    }
                }
            },
            stateSaveParams: function(settings, data) {
                data.__filters = {};
                $filters.find('input, select').each(function (index, input) {
                    var res = getInputNameAndValue(input);
                    if (res.value !== null && res.value !== '') {
                        data.__filters[''+res.name] = ''+res.value;
                    }
                });
            },
            stateLoadParams: function(settings, data) {
                if (typeof data.__filters === 'object') {
                    $filters.find('input, select').each(function (index, input) {
                        var res = getInputNameAndValue(input);
                        if (typeof data.__filters[res.name] !== 'undefined') {
                            $(input).val(data.__filters[res.name]);
                        }
                    });
                }
            },
            //pagingType: "scrolling',
            pageLength: 20
        };


        // Init table:
        settings = $.extend(defaultSettings, settings);
        var table = $table.DataTable(settings);

        // Start searching events:
        $filters.find('input').on('keyup', function() {
            reDrawTableOnFilterInputEvent(this, table);
        });
        $filters.find('select, input[type=\'checkbox\'], input[type=\'date\']').on('change', function() {
            reDrawTableOnFilterInputEvent(this, table);
        });
        $filters.find('.datepicker').on('dp.change', function() {
            reDrawTableOnFilterInputEvent(this, table);
        });

        return table;
    }


    var getInputNameAndValue = function(input) {
        var getLastChunkOfInputName = function(name)
        {
            name = name + '';
            var chunks = [];
            var parts = name.split('[');
            for (var i=0; i < parts.length; i++) {
                var part = parts[i];
                if (part.substr(-1) === ']') {
                    part = part.substr(0, part.length - 1);
                    chunks.push(part);
                }
            }

            return chunks.pop();
        }

        var item = $(input);
        var type = item.prop('type');
        var name = '_filter[' + getLastChunkOfInputName(item.attr('name')) + ']';
        var nodeName = input.nodeName.toLowerCase();
        var value = null;

        // Detect value and name:
        if (nodeName === 'select' && item.prop('multiple')) {
            if (item.val() instanceof Array && item.val().length > 0) {
                value = item.val();
            }
            name = '_filter[' + item.attr('name').replace('[]', '') + ']';
        }
        else if ((type === 'checkbox' || type === 'radio')) {
            if (item.prop('checked')) {
                value = item.val();
            }
        } else {
            value = '' + item.val();
        }

        return {
            name: name,
            value: value
        }
    }

}(this.DataTablesListing = this.DataTablesListing || {}));
