document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('.product-tab');
    const panels = document.querySelectorAll('.tab-panel');

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            event.preventDefault();
            // Remove active class from all buttons and panels
            buttons.forEach(btn => btn.classList.remove('active'));
            panels.forEach(panel => panel.classList.remove('active'));

            // Add active class to the clicked button and the corresponding panel
            button.classList.add('active');
            document.getElementById(button.getAttribute('data-tab')).classList.add('active');
        });
    });
});
jQuery(document).ready(function ($) {
    // function checkEditorHeader() {
        // const editorHeader = $('.editor-header__settings');

        // if (editorHeader.length > 0) {
        //     clearInterval(editorHeaderInterval);
//$('#upload-product-import-btn').on('click', function () {
    $('#upload-product-import-btn').off('click').on('click', function () {

    const fileInput = $('#product-import-file')[0];
    const file = fileInput.files[0];

    if (!file) {
        console.error("No file selected.");
        $('#product-import-status').text("No file selected. Please choose a file.").css('color', '#FF0000');
        return;
    }

   // const postId = wp.data.select('core/editor').getCurrentPostId();
   const urlParams = new URLSearchParams(window.location.search);
   const postId = urlParams.get('post');
    const formData = new FormData();
    formData.append('action', 'handle_import_csv');
    formData.append('file', file);
    formData.append('post_id', postId);

    $('#upload-product-import-btn').prop('disabled', true).text('Uploading...').css({
        'background-color': '#ccc',
        'cursor': 'not-allowed',
    });

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            try {
                const data = JSON.parse(response) || response;
                if (data.success || response.success) {
                    $('#smack-message').show().text(data.message);
                    $('#product-import-file').prop('disabled', true);
                    $('#product-import-file').next().hide();
                    $('#upload-product-import-btn').hide();
                    $('#clear-btn').show();
                    $('#product-import-status').hide();
                    $('#smack-message').append(`
                        <p>
                            <a href="${data.redirect_link}" target="_blank" style="color: #007cba; text-decoration: none; font-weight: bold;">
                                Click here
                            </a> for Redirect link
                        </p>
                     `);
                } else if (!data.success || !response.success) {
                    $('#smack-imp-message').show().text(response.data.message || data.message).css('color', '#FFFFF');

                } else {

                    $('#smack-imp-message').show().text("Something Went to wrong" + response.data.message || data.message).css('color', '#FFFFF');

                }
            } catch (e) {
                console.error("Error parsing JSON response:", e);
                $('#product-import-status').text("Upload Failed. Invalid response format.").css('color', '#FF0000');
            } finally {
                $('#upload-product-import-btn').prop('disabled', false).text('Upload Import').css({
                    'background-color': '#007cba',
                    'cursor': 'pointer',
                });
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", error);
            $('#product-import-status').text("Upload Failed. Please try again.").css('color', '#FF0000');
            $('#upload-product-import-btn').prop('disabled', false).text('Upload Import').css({
                'background-color': '#007cba',
                'cursor': 'pointer',
            });
        }
        // clearInterval(editorHeaderInterval); // Stop the interval after attaching the handler
    });
    clearInterval(editorHeaderInterval)
});

$('#product-import-file').on('change', function () {
    const fileName = $(this).val().split('\\').pop();
    if (!fileName) {
        $('#upload-product-import-btn').hide();
        return;
    }

    $('.loading-bar').show();
    $('#product-import-status').hide();
    $('#upload-product-import-btn').hide().prop('disabled', true);

    let truncatedFileName = fileName;
    if (fileName.length > 10) {
        truncatedFileName = fileName.substring(0, 10) + '...csv';
    }

    $('#product-import-status').show().text(`Importing ${truncatedFileName}`).css('color', '#017C01');

    let progress = 0;
    const interval = setInterval(function () {
        progress += 10;
        // $('#loading-progress').css('width', progress + '%');
        $('#loading-progress').css({
            'width': progress + '%',
        });
        
        if (progress >= 100) {
            clearInterval(interval);
            $('#product-import-status').text(`Success!`).css('color', '#017C01');
            $('#upload-product-import-btn')
                .prop('disabled', false)
                .css({
                    'margin-top': '20px',
                    'display': 'block',
                })
                .show();
        }
    }, 200);
});
//$('#product-export-btn').on('click', function () {
    $('#product-export-btn').off('click').on('click', function () {
    const urlParams = new URLSearchParams(window.location.search);
   const postId = urlParams.get('post');
   const postTitle = 'testtitle';
   event.preventDefault();

    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'handle_export_csv',
            post_id: postId,
            post_title: postTitle,
        },
        
        success: function (response) {
            console.log('response', 'color: #0088cc', typeof (response));
            try {
                // Check if response is a string and parse it if needed
                const data = (typeof response === 'string') ? JSON.parse(response) : response;

                if (data.success) {
                    const filePath = data.file_path;
                    const fileName = filePath.split('/').pop();
                    const downloadLink = document.createElement('a');
                    downloadLink.href = filePath;
                    downloadLink.download = fileName;
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                } else if (!data.success) {
                    $('#smack-product-exp-message').show().text(response.data.message || data.message).css('color', '#FF0000');
                } else {
                    $('#smack-product-exp-message').show().text(data.message || "Something went wrong").css('color', '#FF0000');
                }
            } catch (e) {
                console.error("Error parsing JSON response:", e);
                $('#smack-product-exp-message').show().text("Error parsing response").css('color', '#FF0000');
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", error);
        }
    });
});


$('#clear-btn').on('click', function () {
    $('#import-file').val('');
    $('#smack-message').text('')
    $('#import-status').text('');
    $('#smack-message').hide();
    $('#clear-btn').hide();
    $('#upload-import-btn').show();
    $('#import-file').prop('disabled', false);
});
    // }
// }
    // const editorHeaderInterval = setInterval(checkEditorHeader, 500);
});
