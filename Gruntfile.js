module.exports = function(grunt) {

    // Project configuration.
    grunt.initConfig({

        pkg: grunt.file.readJSON('package.json'),

        docular: {
            groups: [
                {
                    groupTitle: 'Change',
                    groupId: 'change',
                    groupIcon: 'icon-globe',
                    sections: [
                        {
                            id: "change",
                            title: "API reference",
                            showSource: false,
                            scripts: [
                                "Plugins/Modules/Rbs/Admin/Assets/js"
                            ]
                        },
                        {
                            id: "editors",
                            title: "Document editors",
                            showSource: false,
                            docs: [
                                "Plugins/Modules/Rbs/Admin/Docs/editors/"
                            ],
                            rank : {'index':1, 'templates':2, 'fields':3, 'sections':4, 'logic':5}
                        },
                        {
                            id: "lists",
                            title: "Document lists",
                            showSource: false,
                            docs: [
                                "Plugins/Modules/Rbs/Admin/Docs/lists/"
                            ],
                            rank : {'template':1, 'directives':2, 'rbsDocumentList':3, 'columns':4, 'preview':5, 'gridmode':6, 'actions':7, 'quickactions':8, 'createlinks':9, 'listcontents':10}
                        }
                    ]
                }
            ],
            showDocularDocs: false,
            showAngularDocs: false,
            angularStartSymbol: '(=',
            angularEndSymbol: '=)',
            docular_webapp_target: "www/ChangeDocs",
            baseUrl: '/ChangeDocs/',
            docular_partial_home: 'Plugins/Modules/Rbs/Admin/Docs/docular_partial_home.html'
        }

    });

    // Load the plugin that provides the "docular" tasks.
    grunt.loadNpmTasks('grunt-docular');

    // Default task(s).
    grunt.registerTask('default', ['docular']);

};
