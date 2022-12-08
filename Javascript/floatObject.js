/**
 * Created by xiedalie on 2016/11/28.
 */

/**
 * floatObject 包含加减乘除四个方法，能确保浮点数运算不丢失精度
 *
 * http://www.cnblogs.com/snandy/p/4943138.html
 *
 * 我们知道计算机编程语言里浮点数计算会存在精度丢失问题（或称舍入误差），其根本原因是二进制和实现位数限制有些数无法有限表示
 * 以下是十进制小数对应的二进制表示
 *      0.1 >> 0.0001 1001 1001 1001…（1001无限循环）
 *      0.2 >> 0.0011 0011 0011 0011…（0011无限循环）
 * 计算机里每种数据类型的存储是一个有限宽度，比如 JavaScript 使用 64 位存储数字类型，因此超出的会舍去。舍去的部分就是精度丢失的部分。
 *
 * ** method **
 *  add / subtract / multiply /divide
 *
 * ** example **
 *  0.1 + 0.2 == 0.30000000000000004 （多了 0.00000000000004）
 *  0.2 + 0.4 == 0.6000000000000001  （多了 0.0000000000001）
 *  19.9 * 100 == 1989.9999999999998 （少了 0.0000000000002）
 *
 * floatObject.add(0.1, 0.2) >> 0.3
 * floatObject.multiply(19.9, 100) >> 1990
 *
 */
const floatObject = function () {
    /*
     * 判断obj是否为一个整数
     */
    function isInteger(obj) {
        return Math.floor(obj) === obj
    }

    /*
     * 将一个浮点数转成整数，返回整数和倍数。如 3.14 >> 314，倍数是 100
     * @param floatNumber {number} 小数
     * @return {object}
     *   {times:100, num: 314}
     */
    function toInteger(floatNumber) {
        let result = {times: 1, num: 0};
        if (isInteger(floatNumber)) {
            result.num = floatNumber;
            return result
        }
        const strNumber = floatNumber + '';
        const dotPos    = strNumber.indexOf('.');
        const len       = strNumber.substr(dotPos + 1).length;
        const times     = Math.pow(10, len);
        const intNumber = parseInt(floatNumber * times + 0.5, 10);
        result.times    = times;
        result.num      = intNumber;
        return result
    }

    function toFixed(num, s) {
        const times = Math.pow(10, s);
        let des     = num * times + 0.5;
        des         = parseInt(des, 10) / times;
        return des + ''
    }

    /*
     * 核心方法，实现加减乘除运算，确保不丢失精度
     * 思路：把小数放大为整数（乘），进行算术运算，再缩小为小数（除）
     *
     * @param a {number} 运算数1
     * @param b {number} 运算数2
     * @param digits {number} 精度，保留的小数点数，比如 2, 即保留为两位小数
     * @param op {string} 运算类型，有加减乘除（add/subtract/multiply/divide）
     *
     */
    function operation(a, b, digits, op) {
        const o1 = toInteger(a);
        const o2 = toInteger(b);
        const n1 = o1.num;
        const n2 = o2.num;

        const t1 = o1.times;
        const t2 = o2.times;

        const max  = t1 > t2 ? t1 : t2;
        let result = null;

        switch (op) {
            case 'add':
                if (t1 === t2) { // 两个小数位数相同
                    result = n1 + n2;
                } else if (t1 > t2) { // o1 小数位 大于 o2
                    result = n1 + n2 * (t1 / t2);
                } else { // o1 小数位 小于 o2
                    result = n1 * (t2 / t1) + n2;
                }

                result = result / max;
                break;
            case 'subtract':
                if (t1 === t2) {
                    result = n1 - n2;
                } else if (t1 > t2) {
                    result = n1 - n2 * (t1 / t2);
                } else {
                    result = n1 * (t2 / t1) - n2;
                }
                result = result / max;
                break;
            case 'multiply':
                result = (n1 * n2) / (t1 * t2);
                break;
            case 'divide':
                result = (n1 / n2) * (t2 / t1);
                break;
        }

        if (digits) {
            result = toFixed(result, digits);
        }

        return result;
    }

    // 加减乘除的四个接口
    function add(a, b, digits) {
        return operation(a, b, digits, 'add');
    }

    function subtract(a, b, digits) {
        return operation(a, b, digits, 'subtract');
    }

    function multiply(a, b, digits) {
        return operation(a, b, digits, 'multiply');
    }

    function divide(a, b, digits) {
        return operation(a, b, digits, 'divide');
    }

    // exports
    return {
        add     : add,
        subtract: subtract,
        multiply: multiply,
        divide  : divide
    }
}();
