import xxep from '@/xxep';
// #ifdef H5
import $wxsdk from '@/xxep/libs/sdk-h5-weixin';
// #endif
import {
	getRootUrl
} from '@/xxep/helper';

/**
 * 支付
 *
 * @param {String} payment = ['wechat','alipay','wallet','offline']  	- 支付方式
 * @param {String} orderType = ['goods','recharge','groupon']  	- 订单类型
 * @param {String} orderSN					- 订单号
 */

export default class SheepPay {
	constructor(payment, orderType, orderSN) {
		this.payment = payment;
		this.orderSN = orderSN;
		this.orderType = orderType;
		this.payAction();
	}

	payAction() {
		const payAction = {
			WechatOfficialAccount: {
				wechat: () => {
					this.wechatOfficialAccountPay();
				},
				alipay: () => {
					this.redirectPay(); // 现在公众号可以直接跳转支付宝页面
				},
				money: () => {
					this.moneyPay();
				},
				offline: () => {
					this.offlinePay();
				}
			},
			WechatMiniProgram: {
				wechat: () => {
					this.wechatMiniProgramPay();
				},
				alipay: () => {
					this.copyPayLink();
				},
				money: () => {
					this.moneyPay();
				},
				offline: () => {
					this.offlinePay();
				}
			},
			App: {
				wechat: () => {
					this.wechatAppPay();
				},
				alipay: () => {
					this.alipay();
				},
				money: () => {
					this.moneyPay();
				},
				offline: () => {
					this.offlinePay();
				}
			},
			H5: {
				wechat: () => {
					this.wechatWapPay();
				},
				alipay: () => {
					this.redirectPay();
				},
				money: () => {
					this.moneyPay();
				},
				offline: () => {
					this.offlinePay();
				}
			},
		};
		return payAction[xxep.$platform.name][this.payment]();
	}

	// 预支付
	prepay() {
		return new Promise((resolve, reject) => {
			let data = {
				order_sn: this.orderSN,
				payment: this.payment,
			};
			if (uni.getStorageSync('openid')) {
				data.openid = uni.getStorageSync('openid');
			}
			xxep.$api.pay.prepay(data).then((res) => {
				res.code === 1 && resolve(res);
				if (res.data === -1 && res.msg === 'miss_openid') {
					uni.showModal({
						title: '微信支付',
						content: '请先绑定微信再使用微信支付',
						success: function (res) {
							if (res.confirm) {
								xxep.$platform.useProvider('wechat').bind();
							}
						},
					});
				}
			});
		});
	}
	// #ifdef H5
	// 微信公众号JSSDK支付
	async wechatOfficialAccountPay() {
		let that = this;
		let {
			code,
			data,
			msg
		} = await this.prepay();
		if (code !== 1) {
			console.log('支付错误', msg);
			return;
		}
		$wxsdk.wxpay(data.pay_data, {
			success: () => {
				that.payResult('success');
			},
			cancel: () => {
				xxep.$helper.toast('支付已手动取消');
			},
			fail: () => {
				that.payResult('fail');
			},
		});
	}

	//浏览器微信H5支付
	async wechatWapPay() {
		const {
			code,
			data
		} = await this.prepay();
		if (code === 1) {
			const redirect_url = `${getRootUrl()}pages/pay/result?orderSN=${this.orderSN}&payment=${this.payment
				}&orderType=${this.orderType}`;
			location.href = `${data.pay_data.h5_url}&redirect_url=${encodeURIComponent(redirect_url)}`;
		}
	}

	// 支付链接
	async redirectPay() {
		let {
			code,
			data
		} = await this.prepay();
		if (code === 1) {
			const redirect_url = `${getRootUrl()}pages/pay/result?orderSN=${this.orderSN}&payment=${this.payment
				}&orderType=${this.orderType}`;
			location.href = data.pay_data + encodeURIComponent(redirect_url);
		}
	}

	// #endif

	// 微信小程序支付
	async wechatMiniProgramPay() {
		let that = this;
		let result = await this.prepay();
		uni.requestPayment({
			provider: 'wxpay',
			...result.data.pay_data,
			success: (res) => {
				that.payResult('success');
			},
			fail: (err) => {
				if (err.errMsg === 'requestPayment:fail cancel') {
					xxep.$helper.toast('支付已手动取消');
				} else {
					that.payResult('fail');
				}
			},
		});
	}

	// 余额支付
	async moneyPay() {
		const {
			code
		} = await this.prepay();
		code === 1 && this.payResult('success');
	}

	// 货到付款
	async offlinePay() {
		const {
			code
		} = await this.prepay();
		code === 1 && this.payResult('success');
	}

	// 支付宝复制链接支付
	async copyPayLink() {
		let that = this;
		let {
			code,
			data
		} = await this.prepay();
		if (code === 1) {
			// 引入showModal 点击确认 复制链接；
			uni.showModal({
				title: '支付宝支付',
				content: '复制链接到外部浏览器',
				confirmText: '复制链接',
				success: (res) => {
					if (res.confirm) {
						xxep.$helper.copyText(data.pay_data);
					}
				},
			});
		}
	}

	// 支付宝支付
	async alipay() {
		let that = this;
		const {
			code,
			data
		} = await this.prepay();
		if (code === 1) {
			uni.requestPayment({
				provider: 'alipay',
				orderInfo: data.pay_data, //支付宝订单数据
				success: (res) => {
					that.payResult('success');
				},
				fail: (err) => {
					if (err.errMsg === 'requestPayment:fail [paymentAlipay:62001]user cancel') {
						xxep.$helper.toast('支付已手动取消');
					} else {
						that.payResult('fail');
					}
				},
			});
		}
	}

	// 微信支付
	async wechatAppPay() {
		let that = this;
		let {
			code,
			data
		} = await this.prepay();
		if (code === 1) {
			uni.requestPayment({
				provider: 'wxpay',
				orderInfo: data.pay_data, //微信订单数据(官方说是string。实测为object)
				success: (res) => {
					that.payResult('success');
				},
				fail: (err) => {
					err.errMsg !== 'requestPayment:fail cancel' && that.payResult('fail');
				},
			});
		}
	}

	// 支付结果跳转,success:成功，fail:失败
	payResult(resultType) {
		xxep.$router.redirect('/pages/pay/result', {
			orderSN: this.orderSN,
			payment: this.payment, //重新支付的时候使用
			payState: resultType,
			orderType: this.orderType,
		});
	}
}