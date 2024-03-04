const clock = require('date-events');
const {getEtd} = require("./edt");

module.exports = {
    startMonitorEdt(interaction) {
        clock().on('minute', (minute) => {

            if(minute % 15 !== 0)
                return;

            const currentDate = new Date(Date.now());
            const startDate = new Date();
            const endDate = new Date();

            if(currentDate.getDay() === 6 || currentDate.getDay() === 0) {
                startDate.setDate(currentDate.getDate() + (currentDate.getDay() % 5 +1));
                endDate.setDate(currentDate.getDate() + (currentDate.getDay() % 5 +1 + 4));
            } else {
                startDate.setDate(currentDate.getDate() - (currentDate.getDay() - 1));
                endDate.setDate(currentDate.getDate() - (currentDate.getDay() - 1) + 4);
            }

            for (const data of JSON.parse(process.env.CONFIG_EDT)) {
                getEtd(startDate, endDate, interaction, data);
            }
        });
    }
}