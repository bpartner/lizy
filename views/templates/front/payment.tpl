{extends "$layout"}

{block name="content"}
  <a id="lyzi-payment">Payer</a>
  <script>
    let intervalCheckId;
    let transactionId = undefined;

    window.onload = function () {
      window.lyziBuyButton.init({
        buttonId: '{$buttonId}',
        orderRef: 'CURRENTORDER-LZ-{$orderId}',
        price: {$price},
        callbackUrl: '{$callbackUrl nofilter}',
        goods: {
          goodsName: 'products',
          goodsType: '01',
          goodsCategory: 'Z000',
        }
      }, document.getElementById('lyzi-payment'));

      startInterval();
    };


    function startInterval () {
      intervalCheckId = setInterval(checkTransactionId, 2000);
    }

    function checkTransactionId() {
      let tsId = window.lyziBuyButton.getTransactionCode();
      if (tsId !== undefined && tsId !== transactionId) {
        transactionId = tsId;
        let params = {
          transactionId: tsId,
          orderId: '{$orderId}'
        };

        sendPostRequest('{$setTransactionCallback nofilter}', params)
      }
    }


    function sendPostRequest(url, parameters) {
      let xhr = new XMLHttpRequest();

      xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
          if (xhr.status === 200) {
            //success
          }
        }
      };

      xhr.open('POST', url, true);
      xhr.setRequestHeader('Content-Type', 'application/json');
      let jsonData = JSON.stringify(parameters);
      xhr.send(jsonData);
    }
  </script>
{/block}
