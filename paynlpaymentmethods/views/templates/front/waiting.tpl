<html>
<head>
    <title>Pay.nl</title>
    <meta http-equiv="refresh" content="5; URL={$extendUrl}">
    <style>
        * {
            font-family: Verdana;
        }
        Html, body {
            height: 100%;
            margin: 0;
        }
        div {
            display: block;
        }
        #inner {
            position: relative;
            box-sizing: border-box;
            height: 60%;
            margin-top: 2%;
            width: 50%;
            margin-left: -25%;
            left: 50%;
            border: 1px solid #CCC;
            text-align: center;
            padding: 1%;
            padding-top: 10%;
            min-height: 300px;
            background-color: #e4e4e4;
        }
        img {
            display: inline-block;
            margin-bottom: 2%;
        }
        .footer {
            position: absolute;
            bottom: 2%;
            font-size: 11px;
            width: 100%;
            text-align: center;
            left: 0;
        }
    </style>
</head>

<body>
<div id="inner">

    <a href="https://www.pay.nl" target="_blank"><img style="width: 100px" src="/modules/paynlpaymentmethods/views/images/main_pay_logo.png"></a><br>
    Please wait while your order is being processed...<br>

    <div class="footer">
        Support? Please contact <a href="mailto:support@pay.nl?SUBJECT=Prestashop delay module, order:{$order}">support@pay.nl</a> | +31 (0)88-88 66666 | Pay.nl
    </div>
</div>
</body>
</html>