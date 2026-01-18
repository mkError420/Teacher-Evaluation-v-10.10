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
            $form.find('.tes-comment-section').hide();
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
                $form.find('.tes-comment-section').hide();
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
            $form.find('.tes-comment-section').hide();
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
                    var groupedQuestions = {};

                    // Group questions by type
                    $.each(response.data, function(i, question) {
                        var type = question.question_type || 'General';
                        if (!groupedQuestions[type]) {
                            groupedQuestions[type] = [];
                        }
                        groupedQuestions[type].push(question);
                    });

                    // Define display order
                    var typeOrder = ['Explicit Issues', 'Implicit Issues'];
                    for (var t in groupedQuestions) {
                        if (typeOrder.indexOf(t) === -1) {
                            typeOrder.push(t);
                        }
                    }

                    $.each(typeOrder, function(idx, type) {
                        if (groupedQuestions[type]) {
                            var questions = groupedQuestions[type];
                            if (questions.length === 0) return;

                            var sectionDesc = questions[0].question_text;

                            html += '<div class="tes-section" style="margin-bottom: 30px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">';
                            html += '<h3 style="margin-top: 0; color: #2c3e50; border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px;">' + type + '</h3>';
                            html += '<p style="font-size: 1.1em; color: #555; margin-bottom: 25px; font-style: italic; background: #f8f9fa; padding: 10px; border-radius: 4px;">' + sectionDesc + '</p>';

                            $.each(questions, function(j, q) {
                                var options = q.options.split(',').map(function(opt) { return opt.trim(); });
                                var questionLabel = q.sub_question_title ? q.sub_question_title : q.question_text;

                                html += '<div class="tes-question-block" style="margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee;">';
                                html += '<p style="font-weight: bold; color: #000; background-color: #e9ecef; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 1.1em;">' + (j + 1) + '. ' + questionLabel + ' <span style="color:#dc3545;">*</span></p>';
                                html += '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
                                $.each(options, function(k, option) {
                                    html += '<label style="display: inline-flex; align-items: center; padding: 8px 15px; background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 20px; cursor: pointer; transition: all 0.2s ease; user-select: none;">';
                                    html += '<input type="radio" name="answers[' + q.id + ']" value="' + option + '" required style="margin-right: 8px; accent-color: #667eea; cursor: pointer;">';
                                    html += '<span style="color: #444;">' + option + '</span>';
                                    html += '</label>';
                                });
                                html += '</div></div>';
                            });
                            html += '</div>';
                        }
                    });

                    $form.find('.tes-questions-area').html(html);
                    $form.find('.tes-comment-section').show();
                }
            }
        });
    });

    // Submit survey form
    $(document).on('submit', '.tes-survey-form', function(e) {
        e.preventDefault();
 
        var $form = $(this);
        var allAnswered = true;

        $form.find('.tes-question-block').each(function() {
            if ($(this).find('input[type="radio"]:checked').length === 0) {
                allAnswered = false;
                return false;
            }
        });

        if (!allAnswered) {
            alert("Please select all question field");
            return;
        }

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
                    $form.find('.tes-comment-section').hide();
                    
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
