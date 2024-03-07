const {promises: Fs} = require("fs");
const { EmbedBuilder} = require('discord.js');
const puppeteer = require("puppeteer");

module.exports = {
    async getEtd(startDate, endDate, interaction, data) {

        const token = Date.now();

        if (!(startDate instanceof Date) || isNaN(startDate) || !(endDate instanceof Date) || isNaN(endDate)) {
            interaction.reply({content: 'Format des dates non valide', ephemeral: true});
            return;
        }

        const formattedStartDate = startDate.getFullYear() + "-" + (startDate.getMonth() + 1) + "-" + startDate.getDate();
        const formattedEndDate = endDate.getFullYear() + "-" + (endDate.getMonth() + 1) + "-" + endDate.getDate();

        await capture(formattedStartDate, formattedEndDate, data.studentNumber, token);

        const saveEdtJson = await Fs.readFile(process.env.EDT_SAVE_PATH, "utf8");
        let result= {messageId: undefined, channelId: undefined};

        try {
            let saveEdtParsed = JSON.parse(saveEdtJson);

            saveEdtParsed.forEach(element => {
               if(element.hasOwnProperty(data.promo)) {
                   result.messageId = element[data.promo].message;
                   result.channelId = element[data.promo].channel;
                }
            });

            const channel = interaction.channels.cache.get(data.channel);

            if(!result.messageId && data.message)
                result.messageId = data.message;

            if(result.messageId) {
                const message = await channel.messages.fetch(result.messageId);

                const editEmbedMessage = EmbedBuilder.from(message.embeds[0])
                    .setImage('attachment://screenshot-' + token + '.png')
                    .setTimestamp();

                await message.removeAttachments();
                await message.edit({ embeds: [editEmbedMessage], files: [{attachment: "./screenshots/screenshot-" + token + ".png"}]});
            } else {
                const embedMessage = new EmbedBuilder()
                    .setColor('#0099ff')
                    .setTitle(data.promo)
                    .setURL('https://youtu.be/YnNKj6yqJm4')
                    .setAuthor({ name: 'Le grand Hasagi', url: 'https://youtu.be/dQw4w9WgXcQ' })
                    .setThumbnail('https://files.cults3d.com/uploaders/27114712/illustration-file/c4fe11e0-c2ca-4ba1-9ff2-ff93ecb4a56e/Foto-0.webp')
                    .setImage('attachment://screenshot-' + token + '.png')
                    .setTimestamp();

                await channel.send({ embeds: [embedMessage], files: [{attachment: "./screenshots/screenshot-" + token + ".png"}]})
                    .then(async (message) => {
                        saveEdtParsed.push(
                            {
                                [data.promo]: {
                                    channel: message.channel.id,
                                    message: message.id
                                }
                            });

                        try {
                            await Fs.writeFile(process.env.EDT_SAVE_PATH, JSON.stringify(saveEdtParsed), 'utf-8');
                        } catch (error) {
                            console.error(error);
                        }
                    });
            }
        } catch (error) {
            console.error(error);
        }

        try {
            await Fs.unlink("./screenshots/screenshot-" + token + ".png");
        } catch (err) {
            console.error("Error when deleting screenshot", err);
        }
    }
}

async function capture(start, end, studentId, token) {
    const browser = await puppeteer.launch({args: ['--no-sandbox', '--disable-setuid-sandbox']});
    const page = await browser.newPage();
    await page.setViewport({ width: 1260, height: 620 });
    await page.goto(process.env.API_URL + "?s=" + start + "&e=" + end + "&id=" + studentId);
    await page.screenshot({ path: "./screenshots/screenshot-" + token + ".png" });
    await browser.close();
}

async function exists(path) {
    try {
        await Fs.access(path)
        return true
    } catch {
        return false
    }
}