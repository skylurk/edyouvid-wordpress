const activeAddons = loginpressProData.activeAddons;

// Example: Run JS only if 'social-login' addon is active
if (activeAddons.includes( "social-login" )) {
	window.loginpressProviders = [
		{
			class: 'facebook',
			name: 'Facebook Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/facebook.svg" alt="Facebook">`,
			status: loginpressData.socialLoginOption.facebook == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.facebook
	},
		{
			class: 'twitter',
			name: 'Twitter Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/twitter.svg" alt="Twitter">`,
			status: loginpressData.socialLoginOption.twitter == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.twitter
	},
		{
			class: 'gplus',
			name: 'Google Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/google.svg" alt="Google">`,
			status: loginpressData.socialLoginOption.gplus == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.google
	},
		{
			class: 'linkedin',
			name: 'LinkedIn Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/linkedin.svg" alt="LinkedIn">`,
			status: loginpressData.socialLoginOption.linkedin == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.linkedin
	},
		{
			class: 'microsoft',
			name: 'Microsoft Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/microsoft.svg" alt="Microsoft">`,
			status: loginpressData.socialLoginOption.microsoft == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.microsoft
	},
		{
			class: 'apple',
			name: 'Apple Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/apple.svg" alt="Apple">`,
			status: loginpressData.socialLoginOption.apple == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.apple
	},
		{
			class: 'discord',
			name: 'Discord Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/discord.svg" alt="Discord">`,
			status: loginpressData.socialLoginOption.discord == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.discord
	},
		{
			class: 'wordpress',
			name: 'WordPress Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/wordpress.svg" alt="WordPress">`,
			status: loginpressData.socialLoginOption.wordpress == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.wordpress
	},
		{
			class: 'github',
			name: 'Github Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/github.svg" alt="Github">`,
			status: loginpressData.socialLoginOption.github == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.github
	},
		{
			class: 'amazon',
			name: 'Amazon Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/amazon.svg" alt="Amazon">`,
			status: loginpressData.socialLoginOption.amazon == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.amazon
	},
		{
			class: 'pinterest',
			name: 'Pinterest Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/pinterest.svg" alt="Pinterest">`,
			status: loginpressData.socialLoginOption.pinterest == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.pinterest
	},
		{
			class: 'disqus',
			name: 'Disqus Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/disqus.svg" alt="Disqus">`,
			status: loginpressData.socialLoginOption.disqus == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.disqus
	},
		{
			class: 'reddit',
			name: 'Reddit Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/reddit.svg" alt="Reddit">`,
			status: loginpressData.socialLoginOption.reddit == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.reddit
	},
		{
			class: 'spotify',
			name: 'Spotify Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/spotify.svg" alt="Spotify">`,
			status: loginpressData.socialLoginOption.spotify == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.spotify
	},
		{
			class: 'twitch',
			name: 'Twitch Login',
			logo: `<img src="${loginPressGlobal.socialDirPath}/assets/img/twitch.svg" alt="Twitch">`,
			status: loginpressData.socialLoginOption.twitch == 'on' ? 'on' : 'off',
			description: loginpressProviderData.descriptions.twitch
		}
	];
};
