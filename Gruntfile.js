module.exports = function(grunt) {

    // Project configuration.
    grunt.initConfig({

        pkg: grunt.file.readJSON('package.json'),

        docular: {
            groups: [
                {
                    groupTitle: 'Change Admin',
                    groupId: 'changeadmin',
                    groupIcon: 'icon-globe',
                    sections: [
                        {
                            id: "directives",
                            title: "Directives",
                            showSource: false,
                            scripts: [
                                "Plugins/Modules/Rbs/Admin/Assets/js/directives"
                            ]
                        },
						{
							id: "filters",
							title: "Filters",
							showSource: false,
							scripts: [
								"Plugins/Modules/Rbs/Admin/Assets/js/filters"
							]
						},
						{
							id: "services",
							title: "Services",
							showSource: false,
							scripts: [
								"Plugins/Modules/Rbs/Admin/Assets/js/services"
							]
						}
                    ]
                }
            ],
            showDocularDocs: false,
            showAngularDocs: true
        }

    });

    // Load the plugin that provides the "docular" tasks.
    grunt.loadNpmTasks('grunt-docular');

    // Default task(s).
    grunt.registerTask('default', ['docular']);

};
