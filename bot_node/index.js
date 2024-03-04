const { Client, Events, GatewayIntentBits, Collection} = require('discord.js');
const dotenv = require('dotenv');
const fs = require("fs");
const {startMonitorEdt} = require('./module/edtMonitor');

dotenv.config();

const client = new Client({ intents: [GatewayIntentBits.Guilds] });

client.commands = new Collection();
const commandFiles = fs.readdirSync('./commands')
	.filter(file => file.endsWith('.js'));
const eventFiles = fs.readdirSync('./events')
	.filter(file => file.endsWith('.js'));

for (const commandFile of commandFiles) {
	const command = require(`./commands/${commandFile}`);
	client.commands.set(command.data.name, command);
}

for (const eventFile of eventFiles) {
	const event = require(`./events/${eventFile}`);
	if (event.once)
		client.once(event.name, (...args) => event.execute(...args));
	else
		client.on(event.name, (...args) => event.execute(...args));
}

client.once(Events.ClientReady, readyClient => {
	console.log(`Ready! Logged in as ${readyClient.user.tag}`);
});

client.login(process.env.TOKEN).then(() => {
	startMonitorEdt(client);
});