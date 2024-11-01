(function($) {

  $(document).ready(function() {

    $('.woocommerce-loop-product__link').on('click', function() {

      document.cookie = 'sku=; Max-Age=0'; // Delete the 'sku' cookie when the user clicks a product on the search page.
    });
  });

})(jQuery);
