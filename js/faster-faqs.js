document.addEventListener('DOMContentLoaded', function () {
    
    //console.log("faster-faqs running");
    var faqTitles = document.querySelectorAll('.faq-title');

    faqTitles.forEach(function(title) {
        title.addEventListener('click', function() {
            var content = this.nextElementSibling;
            if (content.style.display === 'block') {
                content.style.display = 'none';
            } else {
                content.style.display = 'block';
            }
        });
    });
});


