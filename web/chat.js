$(() => {
    const url = window.location.href;
    const arr = url.split('/');
    let host = 'localhost';
    const protocol = arr[0];
    if (protocol !== 'file:') {
        const currentHost = arr[2];
        const arr2 = currentHost.split(':');
        host = arr2[0];
    }
    const conn = new WebSocket('ws://' + host + ':8081');
    let encryptor = '';

    function decrypt(value) {
        const chars = value.split('');
        let result = '';
        for (let i = 0; i < chars.length; i++) {
            result += String.fromCharCode(chars[i].charCodeAt(0) - encryptor[i % encryptor.length].charCodeAt(0))
        }

        return result;
    }

    conn.onopen = function () {
        const url = new URL(window.location.href);
        const u = url.searchParams.get('u');
        conn.send(JSON.stringify({ action: 'authenticate', value: u || 0 }));
        $('#messages-list').html('');
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
                    .html(decrypt(json.value));
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
                        .append($delete)
                        .append($time)
                        .append($text)
                ).scrollTop(1000);
                break;
            case 'delete':
                $('.message[data-time=' + json.value + ']').remove();
                break;
            case 'password_success':
                $('#password_form').hide();
                $('#form').show();
                $('#messages-list').show();
                break;
            case 'password_error':
                $('#password_error').show();

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

    $('#password_form').submit((event) => {
        event.preventDefault();
        encryptor = $('#password_input').val();
        conn.send(JSON.stringify({ action: 'password', value: encryptor }));
        $('#password_input').val('');
    });
});
