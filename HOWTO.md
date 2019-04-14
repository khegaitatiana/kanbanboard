## Local configuration

1. Enter the project folder by typing `cd foldername`
1. Clone the following repository:
    1. `git clone git@github.com:khegaitatiana/kanbanboard.git`
1. Install composer and then run the command to install packages:
    1. `php composer.phar install`
1. Create `.env` file and set variables based on `.env.example` template
1. Create Github OAuth App in _Personal Settings > Developer Settings > OAuth Apps > New OAuth App_:
    1. Set _Homepage URL_ and _Authorization callback URL_ to http://localhost:8080/ for local testing
1. Copy created **Client ID** and **Client Secret** to `.env`
1. Set Github username and Github repositories to `.env`
    1. Multiple repositories should be separated by `|`:
        `repository1|repository2|repository3`
1. Execute `./runlocal.sh`
1. Go to **localhost:8080** in your browser

---
* Deployed application: https://centra-kanbanboard.herokuapp.com/
      