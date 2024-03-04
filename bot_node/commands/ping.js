const { SlashCommandBuilder } = require('discord.js');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('ping')
        .setDescription('Test bot status'),
    async execute(interaction) {
        await interaction.reply({ content: `ouin !`, ephemeral: true });
    },
};