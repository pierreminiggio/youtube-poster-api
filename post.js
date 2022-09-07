import { upload } from 'youtube-videos-uploader'

const args = process.argv

if (args.length !== 8 && args.length !== 9) {
    console.log('Use like this : node post.js [googleLogin] [googlePassword] [googleRecoveryEmail] [videoFileName] [title] [description] [?proxy]')
    process.exit()
}

/** @type {import('puppeteer').LaunchOptions} puppeteerOptions */
const puppeteerOptions = {
    headless: true,
    args: [
        '--no-sandbox'
    ]
}

if (args.length === 9) {
    puppeteerOptions.args.push('--proxy-server=' + args[8])
}

let logTrace = '';

upload(
    {
        email: args[2],
        pass: args[3],
        recoveryemail: args[4]
    },
    [{
        path: args[5],
        title: args[6],
        description: args[7]
    }],
    puppeteerOptions,
    {
        log: toLog => logTrace += `\r\n Log: ${toLog}`,
        userAction: userAction => userAction += `\r\n Log: ${userAction}`
    }
).then(videoLinks => {
    if (videoLinks && videoLinks.length) {
        console.log(JSON.stringify(videoLinks))
    } else {
        console.log('Error, no video ' + logTrace)
    }

    process.exit();
}).catch(error => {
    console.log(JSON.stringify({error, logTrace}))
    process.exit();
})
