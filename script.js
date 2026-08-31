// Auto-dismiss alerts after 5 seconds
$(document).ready(function() {
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
});