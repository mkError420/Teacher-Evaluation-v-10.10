jQuery(function($) {
    'use strict';

    console.log('=== TES Survey Script Loaded ===');
    console.log('AJAX URL:', tes_ajax.ajax_url);

    if (typeof tes_ajax === 'undefined') {
        console.error('tes_ajax is not defined. AJAX requests will fail.');
        return;
    }

    function loadSurveys(className, $form) {
        $form.find('.tes-no-survey-msg').hide();

        if (!className) {
            $form.find('.tes-survey-select').html('<option value="">Select Teacher Survey</option>').hide().prop('required', false);
            $form.find('.tes-questions-area').empty();
            return;
        }

        console.log('Sending AJAX request for class:', className);
        var studentName = $form.find('input[name="student_name"]').val();
        var studentId = $form.find('input[name="student_id"]').val();
        var phase = $form.find('input[name="phase"]').val();
        
        $.ajax({
            url: tes_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tes_load_surveys_by_class',
                class_name: className,
                student_name: studentName,
                student_id: studentId,
                phase: phase
            },
            dataType: 'json',
            success: function(response) {
                console.log('AJAX Success - Response:', response);
                
                if (response.success && response.data && response.data.length > 0) {
                    console.log('Found', response.data.length, 'surveys');
                    var options = '<option value="">Select Teacher Survey</option>';
                    $.each(response.data, function(i, survey) {
                        console.log('Survey', i, ':', survey);
                        options += '<option value="' + survey.id + '">' + survey.teacher_name + ' - ' + survey.title + '</option>';
                    });
                    $form.find('.tes-survey-select').html(options).show().prop('required', true);
                    console.log('Surveys dropdown updated and shown');
                } else {
                    console.log('No surveys found - success:', response.success, 'data:', response.data);
                    $form.find('.tes-survey-select').html('<option value="">No surveys available</option>').hide().prop('required', false);
                    $form.find('.tes-no-survey-msg').show();
                }
                $form.find('.tes-questions-area').empty();
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response text:', xhr.responseText);
                $form.find('.tes-survey-select').html('<option value="">Error loading surveys</option>').hide().prop('required', false);
                alert('Error loading surveys. Please refresh and try again.');
            }
        });
    }

    // Populate surveys when class is selected (legacy support)
    $(document).on('change', '.tes-class-select', function() {
        loadSurveys($(this).val(), $(this).closest('form'));
    });

    // Auto-load surveys if class is pre-set
    var $autoClass = $('.tes-class-auto');
    if ($autoClass.length > 0) {
        loadSurveys($autoClass.val(), $autoClass.closest('form'));
    }

    // Load questions when survey is selected
    $(document).on('change', '.tes-survey-select', function() {
        var survey_id = $(this).val();
        var $form = $(this).closest('form');

        if (!survey_id) {
            $form.find('.tes-questions-area').empty();
            return;
        }

        $.ajax({
            url: tes_ajax.ajax_url,
            type: 'POST',
            cache: false,
            data: {
                action: 'tes_load_questions',
                survey_id: survey_id
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    var html = '';
                    $.each(response.data, function(i, question) {
                        var options = question.options.split(',').map(function(opt) { return opt.trim(); });
                        
                        html += '<div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border-radius: 8px; border-left: 4px solid #667eea;">';
                        html += '<label style="font-weight: bold; color: #333; display: block; margin-bottom: 8px;">' + question.question_text + '</label>';
                        if (question.sub_question_title) {
                            html += '<p style="color: #666; margin-bottom: 10px; font-size: 0.9em; margin-top: -5px;">' + question.sub_question_title + '</p>';
                        }
                        
                        $.each(options, function(j, option) {
                            html += '<label style="display: inline-block; margin-right: 15px; padding: 8px 12px; background: linear-gradient(145deg, #f0f0f0, #d0d0d0); border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); cursor: pointer; transition: all 0.3s ease; margin-bottom: 5px;">';
                            html += '<input type="radio" name="answers[' + question.id + ']" value="' + option + '" style="margin-right: 5px; transform: scale(1.2); accent-color: #667eea;">';
                            html += option;
                            html += '</label>';
                        });
                        
                        html += '</div>';
                    });
                    $form.find('.tes-questions-area').html(html);
                }
            }
        });
    });

    // Submit survey form
    $(document).on('submit', '.tes-survey-form', function(e) {
        e.preventDefault();
 
        var $form = $(this);
        var formData = new FormData(this);
        formData.append('action', 'tes_submit_survey');

        $.ajax({
            url: tes_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $form.parent().find('.tes-success-msg').show();
                    $form[0].reset();
                    $form.find('.tes-questions-area').empty();
                    
                    // Reload surveys to remove the one just taken
                    var className = $form.find('.tes-class-auto').val();
                    loadSurveys(className, $form);

                    setTimeout(function() {
                        $form.parent().find('.tes-success-msg').fadeOut();
                    }, 3000);
                } else {
                    alert('Error: ' + (response.data || 'An unknown error occurred.'));
                }
            },
            error: function(xhr, status, error) {
                alert('Failed to submit. Please try again.');
            }
        });
    });
});
