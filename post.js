import { upload } from 'youtube-videos-uploader'

const args = process.argv

if (args.length !== 7 && args.length !== 8) {
    console.log('Use like this : node post.js [googleLogin] [googlePassword] [videoFileName] [title] [description] [?proxy]')
    process.exit()
}

/** @type {import('puppeteer').LaunchOptions} puppeteerOptions */
const puppeteerOptions = {
    headless: true,
    args: [
        '--no-sandbox'
    ]
}

if (args.length === 8) {
    args.push('--proxy-server=' + args[7])
}

upload({
    email: args[2],
    pass: args[3]
},[{
    path: args[4],
    title: args[5],
    description: args[6],
}], puppeteerOptions).then(videoLink => {
    console.log(JSON.stringify(videoLink))
})
