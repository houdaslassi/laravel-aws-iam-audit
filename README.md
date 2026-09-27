# Laravel AWS IAM Audit

**Give your Laravel app only the AWS permissions it really needs, and find security problems in your AWS account, right from Artisan.**

```bash
php artisan iam:policy   # generate the minimal IAM policy for your app
php artisan iam:audit    # find security problems in your AWS account
```

---

## About this project

I built this package while practicing **AWS**, to work hands-on with IAM, least privilege and the AWS SDK for PHP in a real Laravel context.

Each new AWS service I work with (S3, SQS, SES...) becomes a new feature. So the package is still young (version `0.x`) and grows step by step. Feedback is very welcome!

---

## Why this package?

Many Laravel apps use AWS. For example, they store user files on **S3**.

To talk to AWS, the app needs an **access key**. And when you create that key, AWS asks you a question: **"Which permissions should this key have?"**

Most developers don't know exactly what to answer. Writing IAM policies is hard, and it's easy to make mistakes. So, to save time, they choose something like:

- `AmazonS3FullAccess` → the app can do **anything** with **every** bucket in the account;
- or even `AdministratorAccess` → the app can do **anything** in the **whole** AWS account.

The app works. Everyone is happy. Until one day, the key **leaks**:

- the `.env` file is pushed to GitHub by mistake;
- `APP_DEBUG=true` is left on in production;
- the server is hacked.

Now the attacker has the same permissions as your app. With `AdministratorAccess`, they can read all your data, delete your backups, or create servers and send you a huge bill.

**This package helps you avoid that.** It reads your Laravel config and tells you exactly which permissions your app needs, and nothing more. It also checks your AWS account for common security problems.

---

## What it does

| Command | What it does |
|---|---|
| `php artisan iam:policy` | Reads your app's config and **generates the minimal IAM policy** your app needs |
| `php artisan iam:audit` | **Checks your AWS account** for common security problems, like users without MFA |

---

## Requirements

- PHP 8.3 or higher
- Laravel 12 or 13
- An AWS account

---

## Installation

```bash
composer require houdaslassi/laravel-aws-iam-audit
```

That's it. Laravel finds the package automatically.

If you want to change the settings, publish the config file:

```bash
php artisan vendor:publish --tag=iam-audit-config
```

This creates `config/iam-audit.php` in your app.

---

## Connecting to AWS

The `iam:audit` command needs to talk to your AWS account. (`iam:policy` only reads your Laravel config, so it doesn't need AWS at all.)

You can connect in several ways. Choose the one that fits your setup:

**1. You already use the AWS CLI on your computer** (`aws configure`)

Nothing to do. The package uses the same credentials automatically.

**2. You have several AWS profiles**

Choose one in your `.env`:

```dotenv
IAM_AUDIT_PROFILE=my-profile
```

**3. You want to use an access key directly**

```dotenv
IAM_AUDIT_KEY=your-access-key-id
IAM_AUDIT_SECRET=your-secret-access-key
```

**4. Your app runs on AWS (EC2, ECS, Lambda...) with an IAM role**

Nothing to do. The package uses the role automatically.

You can also choose the region (optional, IAM is a global service):

```dotenv
IAM_AUDIT_REGION=eu-west-1
```

> **Why `IAM_AUDIT_*` and not the usual `AWS_ACCESS_KEY_ID`?**
> Your app's normal AWS key (the one used for S3) should **not** have IAM permissions. So this package uses its own settings, and your app's key stays limited to what the app needs.

---

## Command 1: `iam:policy`

### What it does

It reads your `config/filesystems.php`, finds your S3 disks, and generates an IAM policy that allows **only** what your app needs:

- only **your bucket**, not the others;
- only **reading, uploading, deleting and listing files**, not deleting the bucket or changing its settings;
- only **your app's folder**, if you use the `root` option.

### Example

Your config:

```php
's3' => [
    'driver' => 's3',
    'bucket' => 'my-app-files',
],
```

Run:

```bash
php artisan iam:policy
```

Output:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "ListS3Bucket",
            "Effect": "Allow",
            "Action": ["s3:ListBucket"],
            "Resource": "arn:aws:s3:::my-app-files"
        },
        {
            "Sid": "ManageS3Objects",
            "Effect": "Allow",
            "Action": ["s3:GetObject", "s3:PutObject", "s3:DeleteObject"],
            "Resource": "arn:aws:s3:::my-app-files/*"
        }
    ]
}
```

### What each part means

| Part | Meaning |
|---|---|
| `"Version": "2012-10-17"` | The version of the policy language. Always use this exact value. It is **not** a date you choose. |
| `"Effect": "Allow"` | This rule gives permission. |
| `"Action"` | What the app is allowed to do. |
| `"Resource"` | On what. |

**Why two rules?** In S3, the **bucket** and the **files inside it** are two different things with two different addresses (ARNs):

- `arn:aws:s3:::my-app-files` → the **bucket**. Listing files is an action on the bucket.
- `arn:aws:s3:::my-app-files/*` → the **files** inside it. Reading, uploading and deleting are actions on files.

Mixing them up is one of the most common IAM mistakes. This package does it right for you.

### Using a folder (`root`)

If your app only uses one folder of the bucket:

```php
's3' => [
    'driver' => 's3',
    'bucket' => 'my-company-bucket',
    'root' => 'uploads',
],
```

The policy is limited to that folder:

- files: `arn:aws:s3:::my-company-bucket/uploads/*`
- listing: only allowed inside `uploads/`, using an IAM **condition**:

```json
"Condition": {
    "StringLike": {
        "s3:prefix": ["uploads", "uploads/*"]
    }
}
```

This is useful when several apps share one bucket. Each app can only see and use its own folder.

### Several S3 disks

If your app has several S3 disks, the policy includes rules for each of them. Each rule gets a unique name (`Sid`) based on the disk name, because AWS requires rule names to be unique:

```php
'documents' => ['driver' => 's3', 'bucket' => 'company-documents'],
'user-avatars' => ['driver' => 's3', 'bucket' => 'company-avatars'],
```

gives rules named `ListDocumentsBucket`, `ManageDocumentsObjects`, `ListUserAvatarsBucket` and `ManageUserAvatarsObjects`.

### Applying the policy in AWS

Save the policy to a file:

```bash
php artisan iam:policy > policy.json
```

Create it in AWS and attach it to your app's IAM user:

```bash
aws iam create-policy --policy-name MyAppS3Access --policy-document file://policy.json

aws iam attach-user-policy --user-name my-app --policy-arn <the-policy-arn>
```

Optional: let AWS check your policy for errors:

```bash
aws accessanalyzer validate-policy --policy-type IDENTITY_POLICY --policy-document file://policy.json
```

If you change your config later (new bucket, new folder), run `iam:policy` again and update the policy in AWS.

### Limits

The command reads your **config**, not your **code**. If your code uses S3 in a way that is not in the config, the policy won't include it. Always test your app after applying a new policy.

---

## Command 2: `iam:audit`

### What it does

It checks your AWS account for common security problems and tells you how to fix them.

```bash
php artisan iam:audit
```

```
+----------+---------------------------------------------------+---------------------------+
| Severity | Problem                                           | How to fix                |
+----------+---------------------------------------------------+---------------------------+
| CRITICAL | The root account has no MFA.                      | Enable MFA on the root... |
| HIGH     | User alice can log in to the console without MFA. | Enable MFA for this user. |
+----------+---------------------------------------------------+---------------------------+
```

The most serious problems are always shown first.

### What it checks

| Check | Severity | Why it matters |
|---|---|---|
| Root account without MFA | Critical | The root account can do **everything**, and it can't be limited by IAM policies. If its password leaks and there's no MFA, the attacker controls your whole account. |
| Users with console access but no MFA | High | If a user's password is stolen (phishing, reused password), the attacker gets all of that user's permissions. |

### Permissions needed for the audit

Don't run the audit with admin credentials. Create a user (or role) with only this policy:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "ReadCredentialReport",
            "Effect": "Allow",
            "Action": [
                "iam:GenerateCredentialReport",
                "iam:GetCredentialReport"
            ],
            "Resource": "*"
        }
    ]
}
```

These two actions only allow reading the **credential report**, a list of users and their security status. Even if this key leaked, nobody could change, create or delete anything.

---

## IAM best practices for backend developers

This package helps with some of these. The others are good habits for every project that uses AWS.

### 1. Least privilege: give only what is needed

Every key should have **only** the permissions its app needs, on **only** the resources it uses. Ask yourself: *"If this key leaks today, what can an attacker do?"* The answer should be: *"Not much."*

This is called limiting the **blast radius**: how far the damage spreads when something goes wrong.

### 2. Narrow both actions and resources

A policy is dangerous when **both** are wide:

```json
"Action": "s3:*",
"Resource": "*"
```

Always prefer specific actions (`s3:GetObject`) and specific resources (`arn:aws:s3:::my-app-files/*`).

### 3. One identity per app (and per environment)

Don't share one key between several apps, or between staging and production. If one leaks, you only need to replace that one, and the others are safe.

### 4. Never use the root account for daily work

The root account is for a few special tasks only (like billing settings). Protect it with MFA, and **never** create access keys for it.

### 5. Prefer IAM roles over access keys

Access keys are long-lived passwords that can leak. When your app runs on AWS (EC2, ECS, Lambda), use an **IAM role** instead: AWS gives the app temporary credentials automatically, and there's no key to leak. In GitHub Actions, use **OIDC** to get temporary credentials without storing any key.

### 6. Never commit your `.env`

Make sure `.env` is in your `.gitignore`. Bots scan GitHub for AWS keys all the time and can find and use a leaked key within minutes.

### 7. Rotate access keys

If you must use access keys, replace them regularly (for example every 90 days). An old key has had more time to leak.

### 8. Turn on MFA for every human user

Passwords get stolen. MFA (a code from an app on your phone) stops most of these attacks.

### 9. Remember: everything is denied by default

In IAM, if something is not explicitly allowed, it is denied. And an explicit `Deny` always wins over any `Allow`.

### 10. If a key leaks: act fast

1. **Deactivate** the key immediately in the IAM console.
2. Create a new key and update your app.
3. Check **CloudTrail** to see what the leaked key was used for.
4. Delete the old key.

---

## Roadmap

- [ ] Public files (`visibility` option) in `iam:policy`
- [x] Support for several S3 disks with unique rule names
- [x] Limit access to one folder with the `root` option
- [ ] `--output` option to save the policy to a file
- [ ] SQS support (queues)
- [ ] SES support (emails)
- [ ] Audit the app's own AWS key: is it too powerful?
- [ ] More audit checks: old access keys, unused keys, admin users

---

## Contributing

Ideas, bug reports and pull requests are welcome. Please open an issue first to discuss big changes.

## Credits

Created by **Houda Slassi**.

## License

MIT. See the [LICENSE](LICENSE) file.
