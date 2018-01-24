$(() => {
    const conn = new WebSocket('ws://localhost:8081?u=1');

    conn.onopen = function () {
        const url = new URL(window.location.href);
        const u = url.searchParams.get('u');
        conn.send(JSON.stringify({ action: 'authenticate', value: u || 0 }));
    };

    conn.onmessage = function (e) {
        const json = JSON.parse(e.data);
        switch (json.action) {
            case 'authenticate':
                break;
            case 'message':
                const time = new Date(json.time);
                const formattedDate = time.toLocaleDateString() + ' ' +
                    time.getHours() + ':' + time.getMinutes();
                const $time = $('<div>')
                    .addClass('time')
                    .html(formattedDate);
                const $text = $('<div>')
                    .addClass('text')
                    .html(json.value);
                const $delete = $('<div>')
                    .addClass('delete')
                    .html('x')
                    .click(() => {
                        conn.send(JSON.stringify({ action: 'delete', value: json.time }));
                    });
                $('#messages-list').append(
                    $('<div>')
                        .addClass('message')
                        .attr('data-client', json.client)
                        .attr('data-time', json.time)
                        .append($time)
                        .append($text)
                        .append($delete)
                );
                break;
            case 'delete':
                $('.message[data-time=' + json.value + ']').remove();
                break;
        }
    };

    conn.onerror = function() {
        $('#messages-list').append(
            $('<div>')
                .addClass('error')
                .html('An error occured...')
        );
        $('#input').hide();
    };

    $('#form').submit((event) => {
        event.preventDefault();
        conn.send(JSON.stringify({ action: 'message', value: $('#input').val() }));
        $('#input').val('');
    });

    $('.delete');
});
