(function($) {
    $(document).ready(function(){
        $(document).on('change', '.user_courses_dropdown', function (e) {
            var dropDown = $(this);

            var course_selected_info = dropDown.children(":selected").attr("id");

            var user_id = course_selected_info.split("_")[0];
            var course_id = course_selected_info.split("_")[1];

            dropDown.attr('disabled', 'disabled');

            var data = {
                'action': 'ldrh_get_course_info_dynamically',
                'u_id': user_id,
                'course_id': course_id
            };

            $.post(variables.ajaxurl, data, function (response) {
                var obj = JSON.parse(response);
                if(obj.status){
                    var firstTD = dropDown.closest('tr').children('td:first').html();
                    var secondTD = dropDown.closest('tr').children('td:nth-child(2)').html();

                    dropDown.closest("tr").html("<td>" + firstTD + "</td><td>" + secondTD + "</td>" + obj.html);

                    var selector = "#" + course_selected_info;

                    $(selector).attr("selected", "selected");

                    $(selector).parent().attr('disabled', false);
                }
            });
        });
    });
})(jQuery);