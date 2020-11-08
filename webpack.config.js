const path = require('path');

module.exports = {
    resolve: {
        alias: {
            '@': path.resolve('resources/js'),
        },
    },
    output: {
        chunkFilename: 'chunks/[name].js',
        publicPath: './'
    },
};
