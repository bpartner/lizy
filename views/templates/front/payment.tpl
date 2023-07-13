{extends "$layout"}

{block name="content"}
<script src="https://admin.lyzi.fr/assets/buy-button/sdk.js" defer></script>
<a id="test-lyzi">Payer</a>
<script>
    window.lyziBuyButton.init({
        buttonId: '640539aba7ab8755cace1edf',
        orderRef: 'CURRENTORDER-FP-2022101001',
        price: 0.1,
        callbackUrl: 'https://webhook.site/1f17c789-f035-4d72-8f9e-2cf2908d5a2e',
        env: 'local',
        goods: {
            goodsName: 'yolo good',
            goodsCategory: '1000',
            goodsType: '01'
        }
    }, document.getElementById('test-lyzi'));
</script>
{/block}




</html>
