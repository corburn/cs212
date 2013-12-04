'use strict';
var tr = $('table:first tr');
function getHeader(i) {
    // Parse the table header into a string array removing whitespace and empty elements
    return $.map(tr.eq(i).text().trim().split('\n'), $.trim).filter(function(v) {
        return v !== '';
    });
}
function getRows(start) {
    var rows = [];
    for (var i = start; i < tr.length; i++) {
        if (tr.eq(i).children('th').length > 0)
            break;
        rows.push(tr.eq(i));
    }
    return rows;
}

var data = [];
/**
 * parseStats recursively parses the table into a JSON string
 * @param {array} data an empty array for building the JSON string
 * @param {int} index table row index
 * @returns {String} JSON string of the statistics data
 */
function parseStats(data, index) {
    // Initialize parameters if they were omitted
    if (typeof data === 'undefined')
        data = [];
    if (typeof index === 'undefined')
        index = 0;
    // Base case
    if (index > tr.length) {
        return JSON.stringify({'statistics': {'stat':data}});
    }
    var header = getHeader(index);
    // Pop year off header array
    var year = header.shift();

    var rows = getRows(index + 1);
    rows.forEach(function(row) {
        var platforms = [];
        var month = row.find('td:first-child').text();
        // If the month is empty, the row is padding between data sets
        if(month === '\xA0') return false;
        // Parse each column
        // Skip the first column which contains the month label
        // Filter empty columns
        row.find('td').not(':first-child').filter(function() {
            return $(this).text() !== '\xA0';
        }).each(function(k, v) {
            platforms.push({
                    'name': header[k],
                    'marketShare': $(v).text()
            });
        });
        data.push({
            'year': year,
            'month': month,
            'platform': platforms
        });
    });
    return parseStats(data, index + rows.length + 1);
}