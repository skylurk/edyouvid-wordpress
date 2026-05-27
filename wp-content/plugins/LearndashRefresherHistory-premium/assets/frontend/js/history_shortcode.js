(function($) {
    $(document).ready(function(){
        $(document).on('change', '.ldrh_course_filter', function (e) {
            var dropDown = $(this);
            var user_id = dropDown.data('user');
            var course_id = dropDown.val();
            if(course_id){
                dropDown.attr('disabled', 'disabled');
                $('.ldrh_loader').show();
                $('.ldrh_history_container').html('');

                var data = {
                    'action': 'ldrh_get_user_course_history',
                    'user_id': user_id,
                    'course_id': course_id
                };

                $.post(variables.ajaxurl, data, function (response) {
                    var obj = JSON.parse(response);
                    if(obj.status){
                        $('.ldrh_history_container').html(obj.html);
                        $('.ldrh_loader').hide();

                        dropDown.attr('disabled', false);
                    }
                });
            }else{
                $('.ldrh_history_container').html('');
            }
        });
    });
})(jQuery);