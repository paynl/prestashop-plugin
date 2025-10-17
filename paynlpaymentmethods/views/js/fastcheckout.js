$(document).ready(function() {
    $(document).on('click', '.fs-product', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const url = $(this).data('url');
        const $addToCartBtn = $('.add-to-cart'); 
        $addToCartBtn.trigger('click');

        $(document).one('ajaxSuccess', function handleAjaxSuccess(event, xhr, settings) {            
            setTimeout(function() {                  
                window.location.href = url; 
            }, 100);          
        });
    });
});