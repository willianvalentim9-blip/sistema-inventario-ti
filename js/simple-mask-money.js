/**
 * Simple Mask Money
 * A simple javascript library to format money inputs.
 *
 * @author Andre Ruffato <andre@marcelof.com>
 * @version 2.1.0
 */
(function () {
    "use strict";

    var SimpleMaskMoney = function () {
    };

    SimpleMaskMoney.args = {};

    SimpleMaskMoney.getArgs = function (el) {
        var defaultArgs = {
            allowNegative: el.hasAttribute("data-allow-negative"),
            negativeSignAfter: el.hasAttribute("data-negative-sign-after"),
            prefix: el.getAttribute("data-prefix") || '',
            suffix: el.getAttribute("data-suffix") || '',
            fixed: el.hasAttribute("data-fixed"),
            fractionDigits: parseInt(el.getAttribute("data-fraction-digits")) || 2,
            decimalSeparator: el.getAttribute("data-decimal-separator") || ',',
            thousandsSeparator: el.getAttribute("data-thousands-separator") || '.',
            cursor: el.getAttribute("data-cursor") || 'end'
        };
        return defaultArgs;
    };

    SimpleMaskMoney.apply = function (el, args) {
        if (!args) {
            args = SimpleMaskMoney.getArgs(el);
        }
        SimpleMaskMoney.args[el.id] = args;
        SimpleMaskMoney.format(el);
        el.addEventListener('input', function () { SimpleMaskMoney.format(el); });
        el.addEventListener('click', function () { SimpleMaskMoney.format(el); });
    };

    SimpleMaskMoney.format = function (el) {
        var args = SimpleMaskMoney.args[el.id];
        var val = el.value;
        var isNegative = false;
        if (val.indexOf("-") > -1) {
            isNegative = true;
        }

        val = val.replace(/[^0-9]/g, '');
        if (val == '') {
            return;
        }

        if (args.fixed) {
            val = val.substring(0, val.length - args.fractionDigits).replace(/^[0]*/g, "") + "." + val.substring(val.length - args.fractionDigits);
            if (val.substring(0, 1) == ".") {
                val = "0" + val;
            }
        } else {
            if (val.length <= args.fractionDigits) {
                val = "0." + "0".repeat(args.fractionDigits - val.length) + val;
            } else {
                val = val.substring(0, val.length - args.fractionDigits) + "." + val.substring(val.length - args.fractionDigits);
            }
        }
        var subval = val.toString().split('.');
        subval[0] = subval[0].replace(/\B(?=(\d{3})+(?!\d))/g, args.thousandsSeparator);
        var newVal = subval[0] + args.decimalSeparator + subval[1];

        if (args.allowNegative) {
            if (args.negativeSignAfter) {
                if (isNegative) {
                    newVal += "-";
                }
            } else {
                if (isNegative) {
                    newVal = "-" + newVal;
                }
            }
        }
        el.value = args.prefix + newVal + args.suffix;

        if (args.cursor === 'end') {
            el.setSelectionRange(el.value.length - args.suffix.length, el.value.length - args.suffix.length);
        } else {
            el.setSelectionRange(args.prefix.length, args.prefix.length);
        }
    };

    SimpleMaskMoney.formatToNumber = function (val) {
        var args = SimpleMaskMoney.getArgs(document.createElement("div")); // Pega argumentos padrão
        var isNegative = false;
        if (val.indexOf("-") > -1) {
            isNegative = true;
        }
        var number = val.replace(/[^0-9]/g, '');
        number = number.substring(0, number.length - args.fractionDigits) + "." + number.substring(number.length - args.fractionDigits);
        number = parseFloat(number);
        if (isNegative) {
            number *= -1;
        }
        return number;
    };

    // Adiciona ao escopo global
    window.SimpleMaskMoney = SimpleMaskMoney;
}());