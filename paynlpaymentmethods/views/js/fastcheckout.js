$(document).ready(function () {
    $(document).on('click', '.fs-product', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const url = $(this).data('url');
        const $addToCartBtn = $('#add-to-cart-or-refresh .add-to-cart');
        $addToCartBtn.trigger('click');

        $(document).one('ajaxSuccess', function handleAjaxSuccess(event, xhr, settings) {
            setTimeout(function () {
                if ($('#fast-checkout-modal').length > 0) {
                    toggleFCModal();
                } else {
                    window.location.href = url;
                }
            }, 100);
        });
    });

    $('#fastcheckout_cart_page.showModal').click(function (e) {
        e.preventDefault();
        toggleFCModal();
        return false;
    });
});

function toggleFCModal() {
    $('#fc-modal-backdrop').removeClass('invisible');
    $('#fc-modal-backdrop').addClass('visible');

    $('#fast-checkout-modal').removeClass('invisible');
    $('#fast-checkout-modal').addClass('visible');

    $('#fast-checkout-modal .button-primary').click(function (e) {
        e.preventDefault();
        window.location.href = $(this).data('url');
        return false;
    });

    $('#fast-checkout-modal .button-secondary').click(function (e) {
        e.preventDefault();
        window.location.href = $(this).data('url');
        return false;
    });

    document.body.style.overflow = 'hidden';
}

function closeModal() {
    $('#fc-modal-backdrop').removeClass('visible');
    $('#fc-modal-backdrop').addClass('invisible');

    $('#fast-checkout-modal').removeClass('visible');
    $('#fast-checkout-modal').addClass('invisible');

    $('#fast-checkout-modal-product').removeClass('visible');
    $('#fast-checkout-modal-product').addClass('invisible');

    document.body.style.overflow = '';
}