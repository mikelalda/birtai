# birtai AI Provider for Moodle

The **birtai AI Provider** is the core engine that connects Moodle with AI services — unlocking a full ecosystem of smart, AI-powered plugins designed to revolutionize online learning.

This provider serves as the central bridge that powers every birtai AI extension, enabling a new generation of intelligent features for teachers, students, and administrators.

## 🔄 Two Ways to Use This Provider

| Mode | Description | Cost |
|------|-------------|------|
| **Local API** | Use your own AI backend (Ollama/HuggingFace) | Free (your hardware) |
| **birtai Cloud** | Use birtai's managed AI services | Pay per credits |

### Local API Mode (Recommended for testing/development)

Run AI models on your own infrastructure using **Ollama** (local LLM) or **HuggingFace Inference API**.

**Advantages:**
- ✅ No costs (use your own hardware)
- ✅ Complete privacy (data never leaves your server)
- ✅ Unlimited usage
- ✅ Works offline (with Ollama)

### birtai Cloud Mode

Use birtai's managed cloud services with pre-configured AI models.

**Advantages:**
- ✅ No infrastructure needed
- ✅ Always up-to-date models
- ✅ Professional support

---

## AI Credit Usage Reports

The **birtai AI Provider** includes built-in capabilities to display **detailed AI credit usage reports** directly within Moodle. Administrators can monitor and manage AI service consumption through visual dashboards showing:

- **Number of credits consumed per month**
- **Available credits**
- **Total credits consumed**
- **Credit distribution by service**
- **Daily credit usage trends**

---

## The birtai AI Plugin Suite

Transform Moodle into a **smarter, faster, and more engaging learning platform** with the **birtai AI Plugin Suite** — a collection of next-generation tools that bring artificial intelligence directly into your LMS.

All plugins in this suite are powered by the **birtai AI Provider**.

### Explore the Suite

- **[Ranking Activities AI](https://moodle.org/plugins/local_birtai_ratings)**
  Empower students to rate course activities while AI analyzes feedback and provides deep insights to educators.

- **[Forum AI](https://moodle.org/plugins/local_forum_ai)**  
  Introduce an AI assistant into your forums that contributes to discussions and keeps engagement alive.

- **[Assign AI](https://moodle.org/plugins/local_assign_ai)**  
  Let AI review student submissions, suggest feedback, and support teachers in the grading process.

- **[Share Certificate AI](https://moodle.org/plugins/local_socialcert)**  
  Celebrate achievements automatically! AI generates personalized social media posts when students earn certificates.

- **[Student Life Story AI](https://moodle.org/plugins/report_lifestory)**  
  Gain a complete view of student performance with AI-generated summaries across all enrolled courses.

- **[Course Creation AI](https://moodle.org/plugins/local_coursegen)**  
  Build full Moodle courses in minutes — complete with lessons, activities, and resources — guided by AI.

---

## Powering Moodle's Built-In AI Features

Beyond the birtai suite, the **AI Provider** also enables Moodle's **native AI features**, enhancing creativity and productivity across your entire platform:

- **Generate Text** – Create meaningful content from any prompt.  
- **Generate Image** – Produce original images from text descriptions.  
- **Summarize Text** – Quickly generate concise summaries from course content.

---

## Pre-requisites

- **Moodle 4.5** or higher

**For Local API Mode:**
- The [moodle-course-generator](../moodle-course-generator) API running
- Ollama installed with a model (e.g., `llama3.2`) OR HuggingFace API token

**For birtai Cloud Mode:**
- Purchase AI credits from the [birtai Shop](https://shop.birtai.com/index.php?m=tokens_manager)
- Create a license key as described in [Getting License Keys](#getting-license-keys)

---

## Installation

### Installing via uploaded ZIP file

1. Log in to your Moodle site as an admin and go to `Site administration > Plugins > Install plugins`.
2. Upload the ZIP file with the plugin code.
3. Check the plugin validation report and finish the installation.

### Installing manually

Copy the plugin contents to:

```
{your/moodle/dirroot}/ai/provider/birtai
```

Then log in as admin and go to `Site administration > Notifications` to complete the installation.

Or run from command line:

```bash
php admin/cli/upgrade.php
```

---

## 🚀 Quick Start: Local API Mode

This is the fastest way to get AI working in your Moodle without any external dependencies or costs.

### Step 1: Start the Local API

```bash
# Navigate to the API folder
cd moodle-course-generator

# Create and activate virtual environment
python -m venv venv
.\venv\Scripts\activate   # Windows
# source venv/bin/activate  # Linux/Mac

# Install dependencies
pip install -r requirements.txt

# Configure (copy and edit .env)
copy .env.example .env

# Start the API
python -m uvicorn app.main:app --port 8001
```

The API will be available at: `http://localhost:8001`
Documentation: `http://localhost:8001/docs`

### Step 2: Start Ollama (if using local LLM)

```bash
# Install a model
ollama pull llama3.2

# Ollama runs automatically on port 11434
```

Or configure HuggingFace in `.env`:

```env
AI_BACKEND=huggingface
HF_TOKEN=hf_your_token_here
```

### Step 3: Configure Moodle

1. Go to `Site administration > General > AI > AI Providers`

   ![AI Providers](./_docs/images/aiprovider_birtai_ai_providers.png)

2. Enable **birtai AI Provider** and click **Settings**

   ![Enable Provider](./_docs/images/aiprovider_birtai_enable_birtai_ai_provider.png)

3. Configure Local API settings:
   - **Use local API**: ✅ Enabled (checked)
   - **Local API URL**: `http://localhost:8001`
   - **License Key**: Leave empty or use any value (not required for local API)

4. Save changes

### Step 4: Configure cURL Settings

For local testing, Moodle's cURL security must allow local connections:

1. Go to `Site administration` and search for "curl"
2. Open **cURL/HTTP security settings**
3. In `cURL blocked hosts list`: Remove `127.0.0.1` and `localhost`
4. In `cURL allowed ports list`: Add `8001` (the API port)

![cURL settings](./_docs/images/aiprovider_birtai_curl_settings.png)

### Step 5: Test the Integration

1. Go to any Moodle page with AI features (e.g., Atto/TinyMCE editor)
2. Try "Generate Text" with a prompt
3. Check the API logs at `http://localhost:8001/docs`

You should see the AI respond with generated content!

---

## birtai Cloud Mode Setup

If you prefer to use birtai's managed cloud services:

### Getting License Keys

1. Sign in or create an account on the [birtai Shop](https://shop.birtai.com)

2. Go to the **Manage AI Credits** section
   
   ![Manage IA Credits](./_docs/images/aiprovider_birtai_manage_ia_credits.png)

3. Click on the **BUY MORE CREDITS** button
   
   ![Buy IA Credits](./_docs/images/aiprovider_birtai_buy_ia_credits.png)
   
4. Select the package of credits you want to acquire
   
   ![Select IA Credits](./_docs/images/aiprovider_birtai_select_ia_credits.png)

5. Once the credits are acquired, the new balance will be displayed
   
   ![New IA Credits](./_docs/images/aiprovider_birtai_new_ia_credits.png)

6. To create a license key, go to **My Licenses** section and click **Create License**
   
   ![Create License](./_docs/images/aiprovider_birtai_create_license.png)

7. Click **Create** in the confirmation window
   
   ![Confirm Create License](./_docs/images/aiprovider_birtai_confirm_create_license.png)
   
8. Click **Assign Credits** to assign credits to your license

   ![Assign IA Credits](./_docs/images/aiprovider_birtai_assign_ia_credits.png)

9. Enter the amount of credits and click **Confirm Assignment**

   ![Confirm Assign](./_docs/images/aiprovider_birtai_confirm_assign_ia_credits.png)

10. Copy the license key for use in Moodle
    
    ![Copy License Key](./_docs/images/aiprovider_birtai_copy_license_key.png)

**Note:** You can create multiple license keys with different credit amounts for different Moodle sites.

### Cloud Mode Configuration

1. Go to `Site administration > General > AI > AI Providers`
2. Enable **birtai AI Provider** and click **Settings**
3. Configure:
   - **Use local API**: ❌ Disabled (unchecked)
   - **License key**: Paste your license key

   ![Config](./_docs/images/aiprovider_birtai_config.png)

---

## birtai Webservice Setup

### ⚠️ IMPORTANT

Without completing this setup, the following birtai AI plugins will not function correctly:

- Forum AI: [Documentation](https://docs.birtai.com/index.php?title=Forum_AI)
- Assign AI: [Documentation](https://docs.birtai.com/index.php?title=Assign_AI)
- Tutor AI: [Documentation](https://docs.birtai.com/index.php?title=Tutor_AI)

### Description

This feature allows automatic configuration of the Web Service used by birtai to fetch contextual information from your platform and improve AI responses.

### How to Use

Go to `Site administration > Server > birtai webservice setup`.

![birtai Webservice Setup](./_docs/images/aiprovider_birtai_birtai_webservice_setup.png)

### What It Does

- Enables Web services and the **REST** protocol
- Creates/reuses the service user `birtaiws`
- Creates/reuses the `birtai web service` role with required capabilities
- Creates/reuses the external service and restricts it to authorized users
- Adds required functions to the service
- Generates a permanent token for the user/service
- Registers the site in birtai (for cloud mode)

### Interface

- Shows current status (web services/REST, user, role, service, token, registration)
  
  ![Webservice Status](./_docs/images/aiprovider_birtai_webservice_status.png)

- Buttons: **Configure webservice**, **Retry**, **Regenerate token**
  
  ![Webservice Buttons](./_docs/images/aiprovider_birtai_webservice_buttons.png)

- Live activity log with steps performed

   ![Webservice Log](./_docs/images/aiprovider_birtai_webservice_log.png)

- Success confirmation

   ![Webservice Success](./_docs/images/aiprovider_birtai_webservice_success.png)

---

## Actions

From the configuration page, you can enable and customize Moodle's AI-powered actions:

- **Generate Text** – Create meaningful content from any prompt.  
- **Generate Image** – Produce original images from text descriptions.  
- **Summarize Text** – Quickly generate concise summaries from course content.

![Actions](./_docs/images/aiprovider_birtai_actions.png)

---

## Reports

Monitor credit usage in detail for each action and plugin.

Go to `Site administration > Reports > General report birtai AI`.

![Report Link](./_docs/images/aiprovider_birtai_page-general-report.png)

### History Consumption Credits 

View credit consumption history for all AI usage.

![History](./_docs/images/aiprovider_birtai_page-report-history.png)

### Visual Reports

Credit usage displayed through graphs.

![Grafics](./_docs/images/aiprovider_birtai_page-report-grafics-bar-pai.png)
![Grafics day](./_docs/images/aiprovider_birtai_page-report-grafics-day.png)

### Plugin List

List of birtai plugins compatible with this provider.

![List plugins](./_docs/images/aiprovider_birtai_page-report-listplugins.png)

---

## Troubleshooting

### Local API not responding

```bash
# Check if API is running
curl http://localhost:8001/health

# Check if Ollama is running
curl http://localhost:11434/api/tags
```

### CORS errors

Add your Moodle URL to the API's allowed origins in `.env`:

```env
ALLOWED_ORIGINS=http://localhost,http://your-moodle-url.com
```

### Timeout errors

Increase timeout in the API `.env`:

```env
OLLAMA_TIMEOUT=600
```

### License key errors (Cloud mode)

Verify your license key has credits assigned in the [birtai Shop](https://shop.birtai.com).

---

## License

2025 Data Curso LLC <https://birtai.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program. If not, see <https://www.gnu.org/licenses/>.
