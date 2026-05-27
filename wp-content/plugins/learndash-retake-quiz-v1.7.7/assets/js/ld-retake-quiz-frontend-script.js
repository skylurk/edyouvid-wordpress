(function($) {

    /**
     * Refresh the page when restart quiz button is clicked.
     */
    $( "input[name='restartQuiz']" ).click(function(e) {
        e.preventDefault();

        // remove previous record error.

        // var quiz_meta=$('.wpProQuiz_content').data('quiz-meta');
        // alert(quiz_meta.quiz_pro_id);

        location.reload();
        return false;
    });
})(jQuery);
