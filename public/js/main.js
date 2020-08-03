$(function () {
    /**
     * Show spinner
     */
    function show_spinner() {
        var spinner_wrapper;

        spinner_wrapper = $('#spinner');

        if (spinner_wrapper.length > 0) {
            spinner_wrapper.remove();
        }

        $('body').append('<div id="spinner"><img src="/images/spinner.svg" alt=""/></div>');

        spinner_wrapper = $('#spinner');
        var spinner = spinner_wrapper.find('img');

        spinner_wrapper.hide().fadeIn();

        spinner_wrapper.css({
            position: 'fixed',
            width: '100%',
            height: '100%',
            top: 0,
            left: 0,
            background: 'rgba(0,0,0, .5)',
            'z-index': 1000000
        });

        spinner.css({
            position: 'absolute',
            top: '50%',
            left: '50%',
            'margin-top': -(spinner.height() / 2),
            'margin-left': -(spinner.width() / 2)
        })
    }

    /**
     * Hide spinner
     */
    function hide_spinner() {
        var spinner = $('#spinner');

        if(spinner.length) {
            spinner.fadeOut(function(){
                $(this).remove();
            });
        }
    }

    /**
     * Show message in default modal window
     *
     * @param message
     */
    function show_modal(message) {
        var modal = $('#modal-default');
        modal.data('show', '1');
        modal.find('.modal-body').empty().html(message);
        modal.modal();
    }

    $(document).on('click', '#draw', function(e) {
        e.preventDefault();

        $.ajax({
            url: 'receivePrize',
            method: 'post',
            dataType: 'json',
            beforeSend: function() {
                return show_spinner();
            }
        }).done(function(data) {
            if (data.status === 'success') {
                $("#my_modal .modal-title").text(data.userName + ', поздравляем, вы выиграли!');

                var message = '';
                switch (data.prizeType) {
                    case 'money':
                        message = 'Ваш выигрыш составляет ' + data.prize + ' грн.';
                        break;
                    case 'points':
                        message = 'Ваш выигрыш составляет ' + data.prize + ' баллов.';
                        break;
                    case 'stuff':
                        message = 'Вы выиграли ' + data.prize + '.';
                        break;
                    default:
                        break;
                }

                $("#my_modal .message").text(message);

                $("#my_modal").modal("show");
            } else {
                $("#my_modal .modal-title").text('Упс!!!');
                $("#my_modal .message").text(data.message);
                $("#my_modal").modal("show");
            }
        }).always(function() {
            hide_spinner();
        });
    });

    $(document).on('click', '#money-to-points', function(e) {
        e.preventDefault();

        $.ajax({
            url: 'convertMoneyIntoPoints',
            method: 'post',
            dataType: 'json',
            beforeSend: function() {
                return show_spinner();
            }
        }).done(function(data) {
            if (data.status === 'success') {
                $("#my_modal .modal-title").text('Операция прошла успешно!');
                $("#my_modal .message").text(data.message);
                $("#my_modal").addClass('reload').modal("show");
            } else {
                $("#my_modal .modal-title").text('Упс!!!');
                $("#my_modal .message").text(data.message);
                $("#my_modal").modal("show");
            }
        }).always(function() {
            hide_spinner();
        });
    });

    $(document).on('click', '.remove-item', function(e) {
        e.preventDefault();

        $.ajax({
            url: 'removePrizeStuff',
            method: 'post',
            dataType: 'json',
            data: {
                stuff_id: $(this).data('stuff-id')
            },
            beforeSend: function() {
                return show_spinner();
            }
        }).done(function(data) {
            if (data.status === 'success') {
                $("#my_modal .modal-title").text('Операция прошла успешно!');
                $("#my_modal .message").text(data.message);
                $("#my_modal").addClass('reload').modal("show");
            } else {
                $("#my_modal .modal-title").text('Упс!!!');
                $("#my_modal .message").text(data.message);
                $("#my_modal").modal("show");
            }
        }).always(function() {
            hide_spinner();
        });
    });

    $(document).on('click', '#transfer-to-the-card', function(e) {
        e.preventDefault();

        $.ajax({
            url: 'paid',
            method: 'post',
            dataType: 'json',
            beforeSend: function() {
                return show_spinner();
            }
        }).done(function(data) {
            if (data.status === 'success') {
                $("#my_modal .modal-title").text('Операция прошла успешно!');
                $("#my_modal .message").text(data.message);
                $("#my_modal").addClass('reload').modal("show");
            } else {
                $("#my_modal .modal-title").text('Упс!!!');
                $("#my_modal .message").text(data.message);
                $("#my_modal").modal("show");
            }
        }).always(function() {
            hide_spinner();
        });
    });

    $(document).on('hidden.bs.modal', function (event) {
        if ('modal reload' === $(event.target).attr('class')) {
            window.location.reload();
        }
    });
})