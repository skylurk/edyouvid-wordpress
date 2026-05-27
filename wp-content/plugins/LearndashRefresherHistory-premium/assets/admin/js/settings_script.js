(function($) {
    $(document).ready(function(){
        $(document).on("change", "input[name='ldrh_email_admin']", function(){
            ShowAdminSection();
        });

        ShowAdminSection();

        $(document).on("change", "input[name='ldrh_email_leaders']", function(){
            ShowleadersSection();
        });

        ShowleadersSection();

        $(document).on("change", "input[name='ldrh_email_student']", function(){
            ShowStudentSection();
        });

        ShowStudentSection();
        
        $(document).on('click', '#ldrh_migrate', function(){
            $.confirm({
                title: variables.migrate_title,
                content: variables.migrate_message,
                useBootstrap: false,
                buttons: {
                    confirm:{
                        btnClass: 'btn-blue',
                        action: function () {
                            $('#ldrh_migrate').attr('disabled', 'disabled');
                            $('#ldrh_migrate_message').show();

                            migrateProcess(1);
                        }
                    },
                    cancel: function () {

                    }
                }
            });
        });
    });
    
    function ShowAdminSection(){
        if($("input[name='ldrh_email_admin']").is(':checked')){
            $('.ldrh_admin_section').show();
        } else {
            $('.ldrh_admin_section').hide();
        }
    }

    function ShowleadersSection(){
        if($("input[name='ldrh_email_leaders']").is(':checked')){
            $('.ldrh_leaders_section').show();
        } else {
            $('.ldrh_leaders_section').hide();
        }
    }

    function ShowStudentSection(){
        if($("input[name='ldrh_email_student']").is(':checked')){
            $('.ldrh_student_section').show();
        } else {
            $('.ldrh_student_section').hide();
        }
    }

    function migrateProcess(offset){
        var data = {
            'action': 'ldrh_migration',
            'offset': offset
        };
        //alert(data.toSource());
        $.post(variables.ajaxurl, data, function (response) {
        //alert('Got this from the server: ' + response);
            var obj = JSON.parse(response);
            if (obj.status) {
                migrateProcess(obj.offset);
                $('#ldrh_completion_percent').html(obj.completed);
            }else{
                $('#ldrh_migrate_container').html('<div style="color: green;">'+obj.message+"</div>");
            }
        });
    }
})(jQuery);