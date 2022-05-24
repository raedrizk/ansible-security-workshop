# Ansible Workshop - Ansible for Red Hat Enterprise Linux Security Automation

**This is documentation for Ansible Automation Platform 2**

If you’re new to Ansible Automation, but want a quicker version of the original RHEL workshop, this 90-minute workshop provides you with fewer exercises, focused on cloud provisioning, converting bash/shell commands to Ansible, all the way to utilizing RHEL System Roles.

**This is documentation for Ansible Automation Platform 2**

## Table of Contents

* [Table of Contents](#table-of-contents)
* [Presentations](#presentations)
* [Time planning](#time-planning)
* [Lab Diagram](#lab-diagram)
* [Ansible Automation Platform Exercises](#ansible-automation-platform-exercises)

## Presentations

The exercises are self explanatory and guide the participants through the entire lab. All concepts are explained when they are introduced.


## Time planning

This workshop was created to last about 90 minutes.

## Lab Diagram

![ansible rhel lab diagram](/images/rhel_lab_diagram.png)

## Ansible Automation Platform Exercises

*************************************************************************************************

# Section 1: Initial Setup

## Step 1 - Setup the Security Workshop Project

The lab environemnt described above will need to be configured before going through the exercises in this workshop, and to do so a playbook has been written to do the following:
* Stop `firewalld` and install, start and enable `auditd` on all the RHEL hosts.
* Install and configure `MariaDB` on `node2` (by default) to act as the backend for our sample webapp. 
    * A sample database `webapp_db` will be created, and populated with a simple table containing information on the name and version of our application. 
    * 2 users, `webapp` and `webappssl` will be created wih
        * `webapp` can connect to the database from any host and does not require an encrypted connection.
        * `webappssl` can connect to the database from only the web server and does require an encrypted connection.
    * Database will not initially be configured to use SSL
    * Databse will be configured to use a non default port , 3389
    * Database will be configured to listen for connections from All hosts
* Install and configure `Apache` and `PHP` on `node1` (by default) to act as our webserver.
    * A simple php page will be deployed to the docroot that connects to the `MariaDB` server and display the name and version of the application
    * The php page will also display information on the status of the database session with regards to the `SSL Cipher` and `SSL Version` if and only if the connection uses SSL.
    * The server listens on port 80 (http), and does not listen on 443 (https).


<br>

In order to import the setup playbook into Automation Controller, we will need to define a new project. 
To start, click **Projects** and click on the ![Add](images/add.png) icon. Use the following values for your new Project:

| Key                              | Value                                                             | Note |
|----------------------------------|-------------------------------------------------------------------|------|
| Name                             | Security Workshop Project                                         |      |
| Description                      | Project containing Playbooks for the RHEL Security Workshop       |      |
| Organization                     | Default                                                           |      |
| Execution Environment            | Leave Blank                                                       |      |
| Source Control Type              | Git                                                               |      |
| Source Control URL               | Clone the URL of the git repo you are reading the instructions on |      |
| Source Control Branch/Tag/Commit | Leave Blank                                                       |      |
| Source Control Refspec           | Leave Blank                                                       |      |
| Source Control Credential        | Leave Blank                                                       |      |

Project Definition will look like this: ![New Project](images/security-project.png)


click on the ![Save](images/save.png) icon, and you will be rediected to the project details page, and a Job will automatically be created to sync the project in Controller. Wait until the `Last Job Status` shows ![Project Successfull](images/project-successful.png). If you get a failure, click the edit button, verify that you entered the project details correctly and save the project.



> **Tip**
>
> We will be setting up another project to host the playbooks we will write during this workshop.


## Step 2 - Create the `Workshop Initial Setup` Job template


Now that the project sync is complete, Select **Templates** and click on the ![Add](images/add.png) icon, and select `Add Job Template`. Use the following values for your new Template:

| Key         | Value                                            | Note |
|-------------|--------------------------------------------------|------|
| Name        | Workshop Initial Setup                           |      |
| Description | Initial setup and configuration of the workshop  |      |
| Job Type    | Run                                              |      |
| Inventory   | Workshop Inventory                               |      |
| Project     | Security Workshop Project                        |      |
| Execution Environment | rhel workshop execution environment             |      |
| Playbook    | `lab-setup.yml`                |      |
| Credential  | Type: **Machine**. Name: **Workshop Credential**     |      |
| Limit       | web                                          |      |
| Options     |                                                  |      |

<br>

![Create Job Template](images/security-setup-template.png)

Click SAVE and then Click LAUNCH to run the job. The job should run successfully and you should be able to see the details of the workshop configuration playbook.

![Run Job Template](images/security-setup-template-output.png)

The initial configuration for the workshop is now complete.

## Step 3 - Validate the setup











To start, we will need to go to our Inventory. So click **Inventories**
on the left panel, and then click the name of our Inventory **Workshop Inventory**. Now that you are on the Inventory Details page, we
will need to go select our Host. So click **HOSTS**.

Next to each host is a checkbox. Check the box next to each host you
want to run an ad-hoc Command on. Select the **Run Command** button.

![Run Command](images/8-chocolatey-adhoc-run-command.png)

This will pop up the **Execute Command** window. Fill out this form as follows:

| Key                   | Value                                  | Note                                                           |
| --------------------- | -------------------------------------- | -------------------------------------------------------------- |
| Module                | `win_chocolatey`                       |                                                                |
| Arguments             | `name=git state=present`               | The name and state of the package                              |
| Limit                 |                                        | This should display the host you selected in the previous step |

Click the **Next** button

| Key                   | Value                                  | Note |
| --------------------- | -------------------------------------- | ---- |
| Execution environment | windows workshop execution environment |      |

Click the **Next** button

| Key                | Value               | Note |
| ------------------ | ------------------- | ---- |
| Machine credential | Workshop Credential |      |
|                    |                     |      |

Click the **Next** button, and once you click **LAUNCH** you will be redirected to the Job log.

![Win\_Chocolatey Job Output](images/8-chocolatey-run-win_chocolatey-result.png)

We see that the output reports a CHANGED status to indicate that `git` was installed. The results also shows a warning that the Chocolatey client was missing from the system, so it was installed as a part of this task run. Future tasks that use the `win_chocolatey` module should now detect the client and use it without the need to install anything. To verify, re-run the job by clicking on the rocketship icon in the **Output** section, the output now should not have a warning, and will also not report any changes, but instead a SUCCESS status as the `win_chocolatey` module (like most Ansible modules) is idempotent (the run will also take less time because as the previous run installed 2 packages, this run installs none).

![Win\_Chocolatey re run Job Output](images/8-chocolatey-rerun-win_chocolatey-result.png)

And just like that we have `git` installed.

## Step 2 - Install multiple packages with specific versions

In the last step we installed one package in an ad-hoc fashion, however in reality it is more likely that we would want to include package installation as one step in a multi step play. It is also likely that we would want to install multiple packages (possibly even specific versions of said packages). In this exercise we will be doing just that.

Let's start by going back to Visual Studio Code. Under the *WORKSHOP_PROJECT* section Create a directory called **chocolatey** and a file called
`install_packages.yml`

You should now have an editor open in the right pane that can be used for creating your playbook.

![Empty install\_packages.yml](images/8-chocolatey-empty-install_packages-editor.png)

First we will define our play:

```yaml
---
- name: Install Specific versions of packages using Chocolatey
  hosts: all
  gather_facts: false
  vars:
    choco_packages:
      - name: nodejs
        version: 13.0.0
      - name: python
        version: 3.6.0
```

Since we will not need or use any of the facts gathered by Ansible,  we have disabled fact gathering by setting `gather_facts: false` to decrease overhead. We also defined one dictionary variable named `choco_packages` under the `vars` directive to hold the names and versions of the packages we want to install using Chocolatey.

Next we will add our tasks:


```yaml
  tasks:

  - name: Install specific versions of packages sequentially
    chocolatey.chocolatey.win_chocolatey:
      name: "{{ item.name }}"
      version: "{{ item.version }}"
    loop: "{{ choco_packages }}"

  - name: Check python version
    ansible.windows.win_command: python --version
    register: check_python_version

  - name: Check nodejs version
    ansible.windows.win_command: node --version
    register: check_node_version

  - ansible.builtin.debug:
      msg: Python Version is {{ check_python_version.stdout_lines[0] }} and NodeJS version is {{ check_node_version.stdout_lines[0] }}
```


We added 4 tasks to the tasks section:

* The first task uses the `win_chocolatey` module from the `chocolatey.chocolatey` collection, and will loop over the `choco_packages` variable to install each product with the specified version
* The second and third tasks use the `win_command` module from the `ansible.windows` collection to execute commands to check the version of `python` and `node` respectively, registering the output of each.
* The fourth and final task used the `debug` module from the `ansible.builtin` collection to display a message containing the information gathered in steps 2 and 3.

> **Tip**
>
> The `win_chocolatey` module's `name` attribute can actually take a list of packages avoiding the need for a loop, however using a loop will allow you to specify the versions of each package, and install them sequentially if order is relevant. for more information on the `win_chocolatey` module take a look at the [docs](https://docs.ansible.com/ansible/latest/collections/chocolatey/chocolatey/win_chocolatey_module.html).

The completed playbook `install_packages.yml` should look like this:

```yaml
---
- name: Install Specific versions of packages using Chocolatey
  hosts: all
  gather_facts: false
  vars:
    choco_packages:
      - name: nodejs
        version: 13.0.0
      - name: python
        version: 3.6.0
  tasks:

  - name: Install specific versions of packages sequentially
    chocolatey.chocolatey.win_chocolatey:
      name: "{{ item.name }}"
      version: "{{ item.version }}"
    loop: "{{ choco_packages }}"

  - name: Check python version
    ansible.windows.win_command: python --version
    register: check_python_version

  - name: Check nodejs version
    ansible.windows.win_command: node --version
    register: check_node_version

  - ansible.builtin.debug:
      msg: Python Version is {{ check_python_version.stdout_lines[0] }} and NodeJS version is {{ check_node_version.stdout_lines[0] }}
```

Now that the playbook is ready:

* Save your work by Clicking `File > Save` from the menu (or using the Ctrl+S shortcut).
* Commit your changes to git - use a relevant commit message such as *Adding install\_packages.yml*.
* Push the committed changes to your repository by clicking the circular arrows.
* (Optional) Verify that your code is in git by going to GitLab using the information under **GitLab Access**.

Now head back to Automation Controller, and sync your Project so that Controller Picks up the new playbook. Click **Projects** and then click the sync icon next to your project.

![Project Sync](images/8-project-sync.png)

Once this is complete, we will create a new job template. Select **Templates** and click on the ![Add](images/add.png) icon, and select Add Job Template. Use the following values for your new Template:

| Key         | Value                                            | Note |
|-------------|--------------------------------------------------|------|
| Name        | Chocolatey - Install Packages                    |      |
| Description | Template for the install_packages playbook       |      |
| Job Type    | Run                                              |      |
| Inventory   | Workshop Inventory                               |      |
| Project     | Ansible Workshop Project                         |      |
| Execution Environment | windows workshop execution environment             |      |
| Playbook    | `chocolatey/install_packages.yml`                |      |
| Credential  | Type: **Machine**. Name: **Workshop Credential**     |      |
| Limit       | windows                                          |      |
| Options     |                                                  |      |

<br>

![Create Job Template](images/8-create-install-packages-job-template.png)

Click SAVE and then Click LAUNCH to run the job. The job should run successfully and you should be able to see Ansible looping and installing the packages specified in our variable

![Run Job Template](images/8-install-packages-job-run-successful.png)

> **Tip**
>
> By now you should be familiar with the flow of creating or editing playbooks, committing your changes and pushing them to git. You should also be comfortable with refreshing your project, creating and running job templates in Automation Controller. Later steps will no longer list each and every step to do so.

## Step 3 - Updating all installed packages

The `win_chocolatey` module can do more than just install packages, it is also used to uninstall and update packages. The action the module does is based on the value you pass to the `state` parameter. Some of the options you can pass include:

* `present`: Will ensure the package is installed.
* `absent` : Will ensure the package is not installed.
* `latest`: Will ensure the package is installed to the latest available version.

The last playbook did not explicitly define and set a value for `state`, so the default value `present` was used as the set value to the state parameter to install packages, however we installed older versions of packages on purpose, so now we want to update those packages.

In Visual Studio Code, create a new file under the `chocolatey` folder with the name `update_packages.yml`. In this playbook we will create a play that uses the `win_chocolatey` module with `latest` passed in as a value to the `state` parameter. Since we want to update all the packages previously installed by Chocolatey, no specific package name will be provided to the `name` parameter, instead the value `all` will be used.

> **Tip**
>
> Information on using `all` as a value that will be set to the `name` attribute can be found in the `win_chocolatey`'s module [docs](https://docs.ansible.com/ansible/latest/collections/chocolatey/chocolatey/win_chocolatey_module.html). Always check the documentation of a module that you are using for the first time, often there will be useful information that will save you a lot of work.

The contents of `update_packages.yml` are:

```yaml
---
- name: Update all packages using Chocolatey
  hosts: all
  gather_facts: false
  tasks:

  - name: Update all installed packages
    chocolatey.chocolatey.win_chocolatey:
      name: all
      state: latest

  - name: Check python version
    ansible.windows.win_command: python --version
    register: check_python_version

  - name: Check nodejs version
    ansible.windows.win_command: node --version
    register: check_node_version

  - ansible.builtin.debug:
      msg: Python Version is {{ check_python_version.stdout_lines[0] }} and NodeJS version is {{ check_node_version.stdout_lines[0] }}
```

The other tasks are there so that we can verify the versions of `nodejs` and `python` after the update task has been run. And that's it, simple right?

Now go ahead and make sure your new playbook is in Git, and that Automation Controller can see it, and then create and run a new Job template with the following values:

> **Tip**
>
> Sine Almost everything will be similar to the first job template we created to install packages, you can `copy` that job template by going to `Tempates` and clicking on the ![copy](images/copy.png) icon next to the `Chocolatey - Install Packages` template. This will create a copy of that template that you can then Edit by clicking on its name, clicking on Edit and making the changes to the name, description and playbook to run. Make sure that the project us updated first otherwise the new playbook will not be available to use. If you prefer you can also create a new jpb template from scratch, the choice is yours.

| Key         | Value                                            | Note |
|-------------|--------------------------------------------------|------|
| Name        | Chocolatey - Update Packages                     |      |
| Description | Template for the update_packages playbook        |      |
| Job Type    | Run                                              |      |
| Inventory   | Workshop Inventory                               |      |
| Project     | Ansible Workshop Project                         |      |
| Execution Environment | windows workshop execution environment             |      |
| Playbook    | `chocolatey/update_packages.yml`                 |      |
| Credential  | Type: **Machine**. Name: **Workshop Credential**     |      |
| Limit       | windows                                          |      |
| Options     |                                                  |      |

After running the new Template, examine the `debug` task message, and compare the versions to the ones from the `install_packages` job output. The versions should be higher as those packages were updates (the `git` package that we installed using an adhoc command will also be checked for an update - unlikely that there will be one after minutes of installation).

![Run Job Template](images/8-update-packages-job-run-successful.png)

# Section 2: Chocolatey facts and configurations

Even though the `win_chocolatey` module is what actually is used to manage packages with Chocolatey, it is not the only Chocolatey module available in the `chocolatey.chocolatey` Ansible collection, there are other modules to help you manage and configure Chocolatey on your Windows targets. In this exercise we will take a look at two of them: `win_chocolatey_facts` and `win_chocolatey_config`

## Step 1 - Gathering Chocolatey facts

The first module we will use is the `win_chocolatey_facts` module from the `chocolatey.chocolatey` collection. This module is used to gather information from Chocolatey, such as installed packages, configuration, features and sources, which is useful for tasks suck as report generation, or conditionals defined on other tasks.

> **Tip**
>
> Read more on the `win_chocolatey_facts` in the [docs](https://docs.ansible.com/ansible/latest/collections/chocolatey/chocolatey/win_chocolatey_facts_module.html).

So let's take a closer look at the information gathered by this module by writing a simple playbook to collect and display the collected information.

In Visual Studio Code, under the `chocolatey` folder, create a new file called `chocolatey_configuration.yml`. The contents of that file should be as follows:

```yaml
---
- name: Chocolatey Facts and Configuration
  hosts: all
  gather_facts: false
  tasks:

  - name: Gather facts from Chocolatey
    chocolatey.chocolatey.win_chocolatey_facts:

  - name: Displays the gathered facts
    ansible.builtin.debug:
      var: ansible_chocolatey
```

The first task uses `win_chocolatey_facts` from the `chocolatey.chocolatey` collection to gather all the available information from Chocolatey on the target Windows machine, and will store this information in a variable named `ansible_chocolatey`, which is using the `debug` module from the `ansible.builtin` collection to print the contents of to examine them closer.

Add your new playbook to your source control repo, and sync your project in Automation Controller, then create and run a new job template with the following values:

| Key         | Value                                            | Note |
|-------------|--------------------------------------------------|------|
| Name        | Chocolatey - Facts and configuration             |      |
| Description | Template for the chocolatey_configuration playbook |      |
| Job Type    | Run                                              |      |
| Inventory   | Workshop Inventory                               |      |
| Project     | Ansible Workshop Project                         |      |
| Execution Environment | windows workshop execution environment             |      |
| Playbook    | `chocolatey/chocolatey_conguration.yml`          |      |
| Credential  | Type: **Machine**. Name: **Workshop Credential**     |      |
| Limit       | windows                                          |      |
| Options     |                                                  |      |

<br>

The output of the job should show you the contents of the `ansible_chocolatey` variable collected in the first task.

![Run Job Template](images/8-chocolatey-configuration-job-run-1-successful.png)

Scroll through the output and observe the values, you can see the configuration of the Chocolatey client on the Windows target, the enabled and disabled features, the installed packages (do you see the packages we installed in previous exercises?) as well as the sources from which we are installing packages (more on this later!). Note that this information is in a JSON format, so you can access individual values by traversing the object tree. For example if I am only interested in information on the installed packages to let's say generate a report of installed packages, I can use the `ansible_chocolatey.packages` key to access those values.

<br>

> **Tip**
>
> We really did not need to use a `debug` task just to see the information collected by the `win_chocolatey_facts` module, instead, in Automation Controller's job output pane click on the result of running the task on the Windows target, which will open the host details dialog for that specific host, which shows information about the host affected by the selected event and the output of that event (In this case, the JSON object returned by the `win_chocolatey_facts` module run can be seen under the `JSON` tab in the dialog box)

<br>

## Step 2 - Configuring Chocolatey

In the previous step, we saw that we can gather the configurations of the Chocolatey client on the windows target using the `win_chocolatey_facts` module, but what if we want to modify those configurations? Well, there is a module for that!

The `win_chocolatey_config` module from the `chocolatey.chocolatey` collection can be used to manage Chocolatey configurations by changing the values of configuration options, or unsetting them all together.

<br>

> **Tip**
>
> Read more on the `win_chocolatey_config` in the [docs](https://docs.ansible.com/ansible/latest/collections/chocolatey/chocolatey/win_chocolatey_config_module.html).

<br>

> **Tip**
>
> Read more on Chocolatey configuration [here](https://docs.chocolatey.org/en-us/configuration).

We will change the values of two configuration options: `cacheLocation` and `commandExecutionTimeoutSeconds`. In the output of the previous step we saw that the `cacheLocation` was unset or did not have a value configured - the default setting, and that the value for `commandExecutionTimeoutSeconds` was set to the default value of 2700. We will modify those configuration options to:

* set `cacheLocation` to `C:\ChocoCache`.
* set `commandExecutionTimeoutSeconds` to 1 hour or `3600` seconds.

In Visual Studio Code, edit the `chocolatey_configuration.yml` playbook, to add the following tasks:

```yaml
  - name: Create a directory for the new Chocolatey caching directory
    ansible.windows.win_file:
      path: C:\ChocoCache
      state: directory

  - name: Configure Chocolatey to use the new directory as the cache location
    chocolatey.chocolatey.win_chocolatey_config:
      name: cacheLocation
      state: present
      value: C:\ChocoCache

  - name: Change the Execution Timeout Setting
    chocolatey.chocolatey.win_chocolatey_config:
      name: commandExecutionTimeoutSeconds
      state: present
      value: 3600

  - name: ReGather facts from Chocolatey after new reconfiguring
    chocolatey.chocolatey.win_chocolatey_facts:

  - name: Displays the gathered facts
    ansible.builtin.debug:
      var: ansible_chocolatey.config
```

These new tasks will perform the following:

* Create the directory `C:\ChocoCache` using the `win_file` module from the `ansible.windows` collection.
* Modify the value of `cacheLocation` to the newly created directory using `win_chocolatey_config` from the `chocolatey.chocolatey` collection.
* Modify the value of `commandExecutionTimeoutSeconds` to `3600`.
* Re gather the Chocolatey facts after modifying the configuration values.
* And Finally print out the `config` section from the refreshed Chocolatey facts.

The contents of the `chocolatey_configuration.yml` playbook should now look like this:

```yaml
---
- name: Chocolatey Facts and Configuration
  hosts: all
  gather_facts: false
  tasks:

  - name: Gather facts from Chocolatey
    chocolatey.chocolatey.win_chocolatey_facts:

  - name: Displays the gathered facts
    ansible.builtin.debug:
      var: ansible_chocolatey

  - name: Create a directory for the new Chocolatey caching directory
    ansible.windows.win_file:
      path: C:\ChocoCache
      state: directory

  - name: Configure Chocolatey to use the new directory as the cache location
    chocolatey.chocolatey.win_chocolatey_config:
      name: cacheLocation
      state: present
      value: C:\ChocoCache

  - name: Change the Execution Timeout Setting
    chocolatey.chocolatey.win_chocolatey_config:
      name: commandExecutionTimeoutSeconds
      state: present
      value: 3600

  - name: ReGather facts from Chocolatey after new reconfiguring
    chocolatey.chocolatey.win_chocolatey_facts:

  - name: Displays the gathered facts
    ansible.builtin.debug:
      var: ansible_chocolatey.config
```

Commit your changes and push them to source control, sync your project in Automation Controller and run the `Chocolatey - Facts and Configuration` job template.
> **Tip**
>
> Back in [exercise 1](../1-tower), when you created the project in Automation Controller, you checked an option to `UPDATE REVISION ON LAUNCH` - so we did not really need to refresh the project in Controller, but just in case that option was missed...

The playbook should run and make the configuration changes, and the output from the last `debug` task showing the value of the `ansible_chocolatey.config` variable should reflect those changes and show the new values for `cacheLocation` and `commandExecutionTimeoutSeconds`.

![Run Job Template](images/8-chocolatey-configuration-job-run-2-successful.png)

<br><br>

And thats it. This exercise covered most Chocolatey related Ansible modules available (with the exception of `win_chocolatey_source` and `win_chocolatey_feature` which you can read about [here](https://docs.ansible.com/ansible/latest/collections/chocolatey/chocolatey/win_chocolatey_feature_module.html) and [here](https://docs.ansible.com/ansible/latest/collections/chocolatey/chocolatey/win_chocolatey_source_module.html). Hopefully you got a taste of the possibilities by using Ansible together with Chocolatey and the `chocolatey.chocolatey` collection to manage your Windows packages.


---
![Red Hat Ansible Automation](images/rh-ansible-automation-platform.png)
