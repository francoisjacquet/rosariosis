# CONTRIBUTING

## New Issue

Before reporting an [issue](https://gitlab.com/francoisjacquet/rosariosis/issues/), please consider the following recommendations:

1. Search [closed issues](https://gitlab.com/francoisjacquet/rosariosis/issues?scope=all&utf8=%E2%9C%93&state=closed) or the [Wiki](https://gitlab.com/francoisjacquet/rosariosis/wikis), your problem may already have been addressed. Please do not create **duplicates**.

2. Specify your RosarioSIS, PHP & PostgreSQL **versions**, along with the server & browser used.

3. Provide **steps to reproduce** the problem.

4. Attach a **screenshot**.

5. RosarioSIS errors, bugs (PHP, SQL, JS errors) & design or logic errors are welcome. _500 Internal Server Error_ messages can be found in the Apache `error.log` file.

6. **Installation problems**: RosarioSIS has been succesfully installed on various environments; nevertheless, you may encounter errors [specific to your OS, PHP or PostgreSQL version or configuration](https://gitlab.com/francoisjacquet/rosariosis/blob/mobile/INSTALL.md#rosariosis-student-information-system). For the same reasons, installation problems will likely not be solved here.

7. **RosarioSIS use**: the Handbooks, the inline Help & the [Wiki](https://gitlab.com/francoisjacquet/rosariosis/wikis) contain useful resources to help you get the most out of RosarioSIS. For all your questions about RosarioSIS use and school administration, you can discuss them in the [forum](https://www.rosariosis.org/forum/).

8. **Email support**: to get professional help with installation problems, or RosarioSIS configuration, please head to https://www.rosariosis.org/services/

You have PHP web development skills? Please head to the next section & send a [merge request](https://docs.gitlab.com/ee/user/project/merge_requests/creating_merge_requests.html).


## Contributing to RosarioSIS

Please head to the offical [Contribute page](https://www.rosariosis.org/contribute) to learn about how you can contribute to the project.

### Coding standards

1. We _roughly_ follow the [Wordpress Coding Standards](https://make.wordpress.org/core/handbook/coding-standards/).

2. [Comment your code](https://make.wordpress.org/core/handbook/best-practices/inline-documentation-standards/): we use PHPDoc.

3. Quality Assurance: we use code linters & other [QA tools](https://phpqa.io/)

4. Testing: Activate [debug mode](https://gitlab.com/francoisjacquet/rosariosis/blob/mobile/INSTALL.md#optional-variables); for emails, we use [MailCatcher](http://mailcatcher.me/)

### Architecture

https://www.rosariosis.org/wp-content/uploads/2016/06/rosariosis-folders-files-architecture.png

### Meta

The [meta](https://gitlab.com/francoisjacquet/rosariosis-meta/) repository provides tools to debug and scripts to run tests, QA and prepare RosarioSIS release.

### Example Module

Freely study and reuse the [Example module](https://gitlab.com/francoisjacquet/Example)

