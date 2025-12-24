# Creating Form Flow Drivers

A comprehensive guide to creating YAML driver configuration files for the Form Flow Manager.

## Table of Contents
1. [Introduction](#introduction)
2. [Two Approaches](#two-approaches)
3. [YAML Schema Reference](#yaml-schema-reference)
4. [Template Syntax](#template-syntax)
5. [Step Definitions](#step-definitions)
6. [Conditional Logic](#conditional-logic)
7. [Variables & Context](#variables--context)
8. [Complete Examples](#complete-examples)
9. [Testing Your Driver](#testing-your-driver)
10. [Best Practices](#best-practices)

---

## Introduction

### What Are Drivers?

Drivers are YAML configuration files that define how to transform domain-specific data into form flow instructions. They enable you to:

- Convert your application's DTOs into multi-step forms
- Define complex conditional flows
- Map data between different structures
- Reuse form flow patterns across features

### How Drivers Work

1. **Auto-Discovery**: Drivers in `config/form-flow-drivers/*.yaml` are automatically loaded on boot
2. **Source/Target Matching**: The `DriverService` finds drivers by matching source/target class names
3. **Transformation**: Driver transforms source data into `FormFlowInstructionsData`
4. **Flow Execution**: Form Flow Manager orchestrates the multi-step form

---

## Two Approaches

### Approach 1: Declarative (No Mappings)

**Best for**: Direct flow definitions where you control the source structure.

**Example**: Voucher redemption flow

```yaml
driver:
  name: "my-flow"
  version: "1.0"
  source: "App\\Data\\MySourceData"
  target: "LBHurtado\\FormFlowManager\\Data\\FormFlowInstructionsData"

# Empty mappings (required but not used)
mappings: {}

# Direct step definitions
steps:
  wallet:
    handler: "form"
    step_name: "wallet_info"
    title: "Payment Details"
    fields:
      - name: "mobile"
        type: "text"
        label: "Mobile Number"
        required: true

callbacks:
  on_complete: "{{ base_url }}/callback/complete"
```

**When to Use**:
- You define the source DTO structure
- Flow structure is static/predictable
- You want clarity over flexibility

---

### Approach 2: Mapping/Transformation

**Best for**: Transforming arbitrary source data into flow steps.

**Example**: Dynamic form builder

```yaml
driver:
  name: "form-builder"
  version: "1.0"
  source: "App\\Data\\FormSchema"
  target: "LBHurtado\\FormFlowManager\\Data\\FormFlowInstructionsData"

mappings:
  # Transform each field into a step
  steps:
    source: "fields"
    transform: "array_map"
    handler:
      field: "{{ item.type }}"
      config:
        field_name: "{{ item.name }}"
        label: "{{ item.label }}"
        required: "{{ item.required ?? false }}"
```

**When to Use**:
- Source data varies dynamically
- Need flexible field-to-step conversion
- Building generic form systems

---

## YAML Schema Reference

### Required Top-Level Keys

```yaml
driver:
  name: string           # Unique driver identifier
  version: string        # Semantic version (e.g., "1.0")
  source: string         # Fully qualified source class name
  target: string         # Fully qualified target class name

mappings: object         # Field mappings (can be empty {})
```

### Optional Top-Level Keys

```yaml
reference_id: string     # Template for flow ID (default: auto-generated)
callbacks: object        # on_complete, on_cancel, on_error URLs
steps: object            # Step definitions (declarative approach)
constants: object        # Constant values
filters: object          # Conditional filters
```

---

## Template Syntax

Drivers use **Twig-style templates** for dynamic values:

### Basic Interpolation

```yaml
title: "Welcome, {{ user.name }}!"
amount: "{{ source.cash.amount }}"
```

### Accessing Nested Properties

```yaml
# Dot notation
owner: "{{ source.owner.name }}"

# Array access
first_field: "{{ source.fields[0].name }}"
```

### Default Values

```yaml
timeout: "{{ config.timeout | default(5) }}"
message: "{{ rider.splash | default('Welcome!') }}"
```

### Filters

```yaml
# Format money
display: "{{ amount | format_money }}"

# Uppercase
code: "{{ voucher.code | upper }}"

# Conditional
enabled: "{{ feature_flag | default('true') }}"
```

### Context Variables

Available in templates:

- `{{ source }}` - Source DTO data
- `{{ code }}` - Voucher code (if applicable)
- `{{ amount }}` - Amount value (if applicable)
- `{{ base_url }}` - Application base URL
- `{{ timestamp }}` - Current timestamp
- Custom variables from your source object

---

## Step Definitions

### Basic Step Structure

```yaml
steps:
  step_name:                    # Unique step identifier
    handler: "form"             # Handler type
    step_name: "internal_name"  # Internal reference
    title: "Step Title"         # Display title
    description: "Details"      # Optional description
    condition: "{{ expr }}"     # Optional: when to show
    config:                     # Handler-specific config
      # ... handler options
```

### Handler Types

#### 1. `splash` - Splash Screen

```yaml
splash:
  handler: "splash"
  step_name: "splash_page"
  config:
    content: "{{ rider.splash }}"
    timeout: 3  # Auto-advance after 3 seconds
    voucher_code: "{{ code }}"
```

#### 2. `form` - Generic Form

```yaml
bio:
  handler: "form"
  step_name: "bio_fields"
  title: "Personal Information"
  config:
    fields:
      - name: "full_name"
        type: "text"
        label: "Full Name"
        required: true
        validation: "required|string|max:255"
      
      - name: "email"
        type: "email"
        label: "Email"
        required: true
      
      - name: "birth_date"
        type: "date"
        label: "Birth Date"
        required: false
```

**Supported Field Types**:
- `text`, `email`, `tel`, `url`, `password`
- `textarea`
- `number`, `date`, `datetime-local`, `time`
- `select`, `checkbox`, `radio`
- `file`
- Custom types (if you have components): `recipient_country`, `bank_account`, `settlement_rail`

#### 3. `location` - GPS Capture

```yaml
location:
  handler: "location"
  step_name: "location_capture"
  title: "Share Location"
  config:
    require_address: true
    capture_snapshot: true
```

#### 4. `selfie` - Camera Capture

```yaml
selfie:
  handler: "selfie"
  step_name: "selfie_capture"
  title: "Take a Selfie"
  config:
    width: 640
    height: 480
    quality: 0.9
```

#### 5. `signature` - Digital Signature

```yaml
signature:
  handler: "signature"
  step_name: "signature_capture"
  title: "Sign Here"
  config:
    width: 600
    height: 256
    quality: 0.85
    line_width: 2
```

#### 6. `kyc` - Identity Verification

```yaml
kyc:
  handler: "kyc"
  step_name: "kyc_verification"
  title: "Identity Verification"
  description: "Complete KYC via HyperVerge"
```

#### 7. `otp` - OTP Verification

```yaml
otp:
  handler: "otp"
  step_name: "otp_verification"
  title: "Verify Phone"
  config:
    code_length: 6
    expiry_minutes: 5
```

---

## Conditional Logic

### Step-Level Conditions

Show/hide entire steps based on conditions:

```yaml
steps:
  kyc:
    handler: "kyc"
    condition: "{{ has_kyc }}"  # Only show if has_kyc is true
  
  bio:
    handler: "form"
    condition: "{{ has_name or has_email }}"
```

### Field-Level Conditions

Show/hide individual form fields:

```yaml
fields:
  - name: "full_name"
    type: "text"
    label: "Name"
    condition: "{{ has_name }}"
  
  - name: "email"
    type: "email"
    label: "Email"
    condition: "{{ has_email }}"
```

### Skip Flow Filters

Prevent entire flow from starting:

```yaml
filters:
  skip_flow_if:
    - "{{ source.already_submitted }}"
    - "{{ empty(source.code) }}"
```

---

## Variables & Context

### Server-Side Variables

Define reusable values in form config:

```yaml
bio:
  handler: "form"
  config:
    variables:
      $kyc_name: "$kyc_verification.name"
      $kyc_email: "$kyc_verification.email"
    
    fields:
      - name: "full_name"
        default: "$kyc_name"  # Auto-populated from KYC
      
      - name: "email"
        default: "$kyc_email"
```

### Cross-Step References

Reference data from previous steps:

```yaml
# Phase 2 context variables
variables:
  $mobile: "$wallet_info.mobile"
  $bank: "$wallet_info.bank_code"

fields:
  - name: "confirm_mobile"
    type: "text"
    default: "$mobile"
    readonly: true
```

### Auto-Sync Fields

Automatically copy values between fields:

```yaml
config:
  auto_sync:
    enabled: true
    source_field: "mobile"
    target_field: "account_number"
    condition_field: "settlement_rail"
    condition_values:
      - "INSTAPAY"
    debounce_ms: 1500
```

---

## Complete Examples

### Example 1: Simple Survey

```yaml
driver:
  name: "customer-survey"
  version: "1.0"
  source: "App\\Data\\SurveyRequest"
  target: "LBHurtado\\FormFlowManager\\Data\\FormFlowInstructionsData"

mappings: {}

steps:
  intro:
    handler: "splash"
    step_name: "intro"
    config:
      content: "Thank you for taking our survey!"
      timeout: 2
  
  questions:
    handler: "form"
    step_name: "survey_questions"
    title: "Customer Feedback"
    config:
      fields:
        - name: "satisfaction"
          type: "select"
          label: "How satisfied are you?"
          options:
            - "Very Satisfied"
            - "Satisfied"
            - "Neutral"
            - "Dissatisfied"
            - "Very Dissatisfied"
          required: true
        
        - name: "comments"
          type: "textarea"
          label: "Additional Comments"
          placeholder: "Tell us more..."
          required: false

callbacks:
  on_complete: "{{ base_url }}/api/survey/submit"
  on_cancel: "{{ base_url }}/survey/cancelled"
```

### Example 2: Multi-Step Onboarding

```yaml
driver:
  name: "user-onboarding"
  version: "1.0"
  source: "App\\Data\\OnboardingRequest"
  target: "LBHurtado\\FormFlowManager\\Data\\FormFlowInstructionsData"

mappings: {}

reference_id: "onboard-{{ user_id }}-{{ timestamp }}"

steps:
  welcome:
    handler: "splash"
    step_name: "welcome"
    config:
      content: "Welcome to {{ app_name }}!"
      timeout: 3
  
  profile:
    handler: "form"
    step_name: "profile_info"
    title: "Your Profile"
    config:
      fields:
        - name: "display_name"
          type: "text"
          label: "Display Name"
          required: true
        
        - name: "bio"
          type: "textarea"
          label: "About You"
          required: false
  
  verify_phone:
    handler: "otp"
    step_name: "phone_verification"
    title: "Verify Phone Number"
    config:
      code_length: 6
  
  profile_photo:
    handler: "selfie"
    step_name: "profile_photo"
    title: "Profile Photo"
    description: "Take a photo for your profile"
    config:
      width: 400
      height: 400

callbacks:
  on_complete: "{{ base_url }}/api/onboarding/complete"
```

### Example 3: Conditional KYC Flow

```yaml
driver:
  name: "kyc-conditional"
  version: "1.0"
  source: "App\\Data\\KYCRequest"
  target: "LBHurtado\\FormFlowManager\\Data\\FormFlowInstructionsData"

mappings: {}

steps:
  kyc:
    handler: "kyc"
    step_name: "kyc_verification"
    title: "Identity Verification"
    condition: "{{ requires_kyc }}"
  
  bio:
    handler: "form"
    step_name: "bio_fields"
    title: "Personal Details"
    config:
      variables:
        $kyc_name: "$kyc_verification.name"
        $kyc_birth: "$kyc_verification.date_of_birth"
      
      fields:
        - name: "full_name"
          type: "text"
          label: "Full Name"
          default: "$kyc_name"
          required: true
        
        - name: "birth_date"
          type: "date"
          label: "Birth Date"
          default: "$kyc_birth"
          required: true

callbacks:
  on_complete: "{{ base_url }}/kyc/complete"
```

---

## Testing Your Driver

### 1. Validate YAML Syntax

```bash
# Check for syntax errors
php artisan tinker
>>> Yaml::parseFile(config_path('form-flow-drivers/my-driver.yaml'))
```

### 2. Test Driver Discovery

```php
use LBHurtado\FormFlowManager\Services\DriverRegistry;

$registry = app(DriverRegistry::class);

// List all discovered drivers
dump($registry->names());

// Get your driver
$driver = $registry->get('my-driver');
dump($driver);
```

### 3. Test Transformation

```php
use LBHurtado\FormFlowManager\Services\DriverService;

$driverService = app(DriverService::class);
$sourceData = MySourceData::from([...]);

// Transform to flow instructions
$instructions = $driverService->transform($sourceData);
dump($instructions);
```

### 4. Start a Test Flow

```php
use LBHurtado\FormFlowManager\Services\FormFlowService;

$flowService = app(FormFlowService::class);
$state = $flowService->startFlow($instructions);

// Visit the flow URL
dump("/form-flow/{$state['flow_id']}");
```

### 5. Write Unit Tests

```php
use Tests\TestCase;

class MyDriverTest extends TestCase
{
    public function test_driver_transforms_correctly()
    {
        $source = MySourceData::from(['field' => 'value']);
        $driverService = app(DriverService::class);
        
        $result = $driverService->transform($source);
        
        $this->assertInstanceOf(FormFlowInstructionsData::class, $result);
        $this->assertCount(3, $result->steps);
    }
}
```

---

## Best Practices

### 1. Naming Conventions

- **Driver name**: Kebab-case, descriptive (`voucher-redemption`, `user-onboarding`)
- **Step names**: Snake_case, unique (`wallet_info`, `kyc_verification`)
- **Field names**: Snake_case (`full_name`, `email_address`)

### 2. Versioning

Use semantic versioning for drivers:

```yaml
driver:
  version: "1.0"  # Initial release
  version: "1.1"  # Backward-compatible additions
  version: "2.0"  # Breaking changes
```

### 3. Error Handling

Provide helpful error messages:

```yaml
callbacks:
  on_error: "{{ base_url }}/flow/error?flow={{ flow_id }}"
```

### 4. Documentation

Add comments to your drivers:

```yaml
# Customer Onboarding Flow
# Version: 1.0
# Purpose: Collects user profile, verifies phone, captures photo
driver:
  name: "user-onboarding"
  # ...
```

### 5. Conditional Steps

Use conditions to create flexible flows:

```yaml
steps:
  advanced_options:
    handler: "form"
    condition: "{{ user.is_premium }}"  # Only for premium users
```

### 6. Validation

Always validate required fields:

```yaml
fields:
  - name: "email"
    type: "email"
    required: true
    validation: "required|email"
```

### 7. Callback URLs

Use absolute URLs for callbacks:

```yaml
callbacks:
  on_complete: "{{ base_url }}/api/complete"  # Not relative paths
```

### 8. Testing Strategy

1. Test with minimal data first
2. Test each conditional branch
3. Test error scenarios
4. Test with real production-like data

---

## Common Patterns

### Pattern 1: Progressive Disclosure

Show fields conditionally based on previous answers:

```yaml
fields:
  - name: "has_account"
    type: "select"
    label: "Do you have an account?"
    options: ["Yes", "No"]
  
  - name: "account_number"
    type: "text"
    label: "Account Number"
    condition: "{{ has_account == 'Yes' }}"
```

### Pattern 2: Data Reuse

Carry data forward through steps:

```yaml
config:
  variables:
    $step1_mobile: "$step1.mobile"
    $step1_email: "$step1.email"
```

### Pattern 3: Validation Chains

Build on validated data:

```yaml
steps:
  verify_phone:
    handler: "otp"
  
  profile:
    handler: "form"
    config:
      variables:
        $verified_phone: "$verify_phone.mobile"
      fields:
        - name: "contact_phone"
          default: "$verified_phone"
          readonly: true
```

---

## Troubleshooting

### Driver Not Found

```
Error: No driver found for source class
```

**Solutions**:
- Check driver `source` matches your DTO fully qualified class name
- Run `php artisan config:clear`
- Verify YAML syntax is valid

### Template Errors

```
Error: Undefined variable in template
```

**Solutions**:
- Check variable exists in source data
- Use default filter: `{{ var | default('fallback') }}`
- Add debug logging to see available context

### Steps Not Showing

```
Flow skips steps unexpectedly
```

**Solutions**:
- Check `condition` expressions
- Verify boolean conditions return true/false
- Check `filters.skip_flow_if` conditions

### Validation Fails

```
Error: Validation failed for field
```

**Solutions**:
- Check field `validation` rules
- Ensure `required` fields have values
- Test with `handle()` method directly

---

## Next Steps

1. **Study Examples**: Check `config/form-flow-drivers/examples/` for patterns
2. **Start Simple**: Create a 2-step flow first
3. **Test Locally**: Use `/form-flow/start` endpoint to test
4. **Add Complexity**: Add conditions, variables, multiple handlers
5. **Write Tests**: Create unit tests for your driver

---

## Resources

- [Form Flow README](../README.md)
- [Plugin Architecture](../PLUGIN_ARCHITECTURE.md)
- [Handler Development Guide](../PLUGIN_ARCHITECTURE.md)
- [Test Examples](../tests/Unit/DriverServiceYamlTest.php)

---

**Questions or Issues?** Check the [GitHub Issues](https://github.com/3neti/form-flow/issues) or create a discussion.
