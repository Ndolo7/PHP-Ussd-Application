<?php
  include 'configs.php';
  $email = "imanindolo@gmail.com";
  $amount = 5;
  $currency = "KES";
  ?>

  <script type="text/javascript">

    function payWithPaystack(e) {
      e.preventDefault();
      let handler = PaystackPop.setup({
        key: '<?php echo $PublicKey; ?>', // Replace with your public key
        email: '<?php echo $email; ?>',
        amount: <?php echo $amount; ?> * 100,
        currency: '<?php echo $currency; ?>', // Use GHS for Ghana Cedis or USD for US Dollars or KES for Kenya Shillings
        //ref: '' + Math.floor((Math.random() * 1000000000) + 1), // generates a pseudo-unique reference. Please replace with a reference you generated. Or remove the line entirely so our API will generate one for you
        // label: "Optional string that replaces customer email"
        onClose: function() {
          alert('Transaction was not completed, window closed.');
        },
        callback: function(response) {
        //   let message = 'Payment complete! Reference: ' + response.reference;
        //   alert(message);
          window.location.href = "verify_transaction.php?reference=" + response.reference;
        }
      });

      handler.openIframe();
    }
  </script>