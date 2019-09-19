'use strict';

const moment = require('moment');
const request = require('request');
let baseUrl = "http://localhost/payfort/services.php?/";


let createCharge = (email,firstName, source, amount) => {

    let chargeCard = () => {
        return new Promise((resolve, reject) => {
            let payfortData = {
                amount: amount,
                merchant_reference: moment().valueOf(),
                token_name: source,
                email: email,
                name: firstName
            }
            let options = {
                method: 'POST',
                url: baseUrl + 'chargeCard',
                headers:
                {
                    'cache-control': 'no-cache',
                    'content-type': 'application/json'
                },
                body: payfortData,
                json: true
            };
            request(options, function (error, response, body) {
                error ? reject(error) : resolve(body)
            })
        });
    };

    return new Promise((resolve, reject) => {
        chargeCard()
            .then((chargeData) => {
                return resolve({ message: "success", data: chargeData })
            }).catch((err) => {
                return reject({ message: err.message });
            });
    });
};

let captureCharge = (req, chargeId, amount) => {
    let refundCharge = () => {
        return new Promise((resolve, reject) => {
            let payfortData = {
                fort_id: chargeId,
                amount: amount,
                merchant_reference: moment().valueOf()
            }
            let options = {
                method: 'POST',
                url: baseUrl + 'captureAmount',
                headers:
                {
                    'cache-control': 'no-cache',
                    'content-type': 'application/json'
                },
                body: payfortData,
                json: true
            };
            request(options, function (error, response, body) {
                error ? reject(error) : resolve(body)
            })
        });
    };
    return new Promise((resolve, reject) => {
        refundCharge()
            .then((chargeData) => {
                return resolve({ message: "success", data: chargeData })
            }).catch((err) => {
                return reject({ message: err.message });
            });
    });
};


let createAndCaptureCharge = (email,firstName, source, amount) => {

    let chargeCard = (data) => {
        return new Promise((resolve, reject) => {
            let payfortData = {
                token_name: source,
                amount: amount,
                email: email,
                name: firstName,
                merchant_reference: moment().valueOf(),
                ip_address: '10.10.10.10'
            }
            let options = {
                method: 'POST',
                url: baseUrl + 'chargeCard',
                headers:
                {
                    'cache-control': 'no-cache',
                    'content-type': 'application/json'
                },
                body: payfortData,
                json: true
            };
            request(options, function (error, response, body) {
                return error ? reject(error) : resolve(body)
            });
        });
    };

    return new Promise((resolve, reject) => {
        chargeCard()
            .then((chargeData) => {
                return resolve({ message: "Success", data: chargeData })
            }).catch((err) => {
                return reject({ message: err.message });
            });
    });
};


let refundCharge = (chargeId, amount) => {
    let refundCharge = () => {
        return new Promise((resolve, reject) => {
            let payfortData = {
                fort_id: chargeId,
                amount: amount,
                merchant_reference: moment().valueOf()
            }
            let options = {
                method: 'POST',
                url: baseUrl + 'refundAmount',
                headers:
                {
                    'cache-control': 'no-cache',
                    'content-type': 'application/json'
                },
                body: payfortData,
                json: true
            };
            request(options, function (error, response, body) {
                error ? reject(error) : resolve(body)
            })
        });
    };
    return new Promise((resolve, reject) => {
        refundCharge()
            .then((chargeData) => {
                return resolve({ message: "success", data: chargeData })
            }).catch((err) => {
                return reject({ message: err.message });
            });
    });
};

module.exports = {
    createCharge,
    captureCharge,
    createAndCaptureCharge,
    refundCharge,
};