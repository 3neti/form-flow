# Form Flow UI Builder - Future Plan

A visual interface for creating and managing form flow drivers without writing YAML.

## Vision

Enable non-technical users to create complex multi-step forms while maintaining the power and flexibility of YAML drivers for advanced users.

---

## Core Concept

**Hybrid Approach**: Visual builder generates YAML, advanced users can edit YAML directly.

```
┌─────────────────┐         ┌──────────────┐         ┌─────────────────┐
│  Visual Builder │ ──────> │  YAML Driver │ ──────> │  Form Flow      │
│  (UI)          │ Generate│  (Config)    │ Execute │  (Runtime)      │
└─────────────────┘         └──────────────┘         └─────────────────┘
                                     ↑
                                     │ Edit
                            ┌────────┴────────┐
                            │  Code Editor    │
                            │  (Advanced)     │
                            └─────────────────┘
```

---

## Phase 1: Read-Only Driver Viewer

**Goal**: View and understand existing drivers visually.

### Features

#### 1. Driver List View
- Table of all drivers in `config/form-flow-drivers/`
- Columns: Name, Version, Source, Target, Steps Count, Active/Example
- Search and filter capabilities
- Status indicators (valid, errors, unused)

#### 2. Driver Detail View
- Visual flow diagram showing steps
- Step cards with handler type and configuration
- Conditional logic visualization (branching paths)
- Source/Target mapping display
- Callback URL preview

#### 3. Step Inspector
- Click any step to see full configuration
- Field list with types and validation rules
- Template variable highlighting
- Condition expression viewer

### Technical Implementation

**Backend**:
```php
// Controllers
DriverController::index()      // List all drivers
DriverController::show($name)  // Show driver details
DriverController::validate()   // Validate YAML syntax

// Services  
DriverVisualizationService::toGraph($driver)  // Convert to graph data
DriverValidationService::check($yaml)         // Validate structure
```

**Frontend**:
```typescript
// Components
DriverList.vue           // Table view
DriverFlowDiagram.vue    // Visual flow with React Flow or Vue Flow
StepCard.vue             // Individual step display
ConditionVisualizer.vue  // Show if/else branches
```

**Routes**:
```
GET  /admin/form-flow/drivers           # List drivers
GET  /admin/form-flow/drivers/{name}    # View driver
POST /admin/form-flow/drivers/validate  # Validate YAML
```

---

## Phase 2: YAML Editor with Autocomplete

**Goal**: Make YAML editing easier with IDE-like features.

### Features

#### 1. Syntax-Aware Editor
- Monaco Editor integration (VS Code engine)
- YAML syntax highlighting
- Real-time validation
- Error markers with helpful messages

#### 2. Autocomplete & IntelliSense
- Handler type suggestions (`splash`, `form`, `location`, etc.)
- Field type completion (`text`, `email`, `date`, etc.)
- Template variable suggestions (`{{ source.* }}`)
- Validation rule autocomplete (`required`, `email`, `max:255`)

#### 3. Schema Validation
- JSON Schema for driver structure
- Inline error messages
- Quick fixes for common issues
- Format on save

#### 4. Live Preview
- Split view: Editor | Preview
- Real-time flow diagram update
- Instant feedback on changes

### Technical Implementation

**Backend**:
```php
// Provide schema for autocomplete
DriverSchemaController::getSchema()

// Validate and return suggestions
DriverController::autocomplete(Request $request)
```

**Frontend**:
```vue
<template>
  <div class="editor-layout">
    <MonacoEditor
      v-model="yaml"
      language="yaml"
      :schema="driverSchema"
      @change="updatePreview"
    />
    <DriverFlowDiagram :driver="parsedDriver" />
  </div>
</template>
```

**Libraries**:
- `monaco-editor` - Code editor
- `monaco-yaml` - YAML language support
- JSON Schema for validation

---

## Phase 3: Visual Step Builder

**Goal**: Create and edit steps without writing YAML.

### Features

#### 1. Drag-and-Drop Flow Builder
- Visual canvas for flow design
- Drag handlers from palette
- Connect steps with arrows
- Rearrange step order visually

#### 2. Step Configuration Panels
- Form-based configuration (no YAML)
- Dynamic forms based on handler type
- Field builder for `form` handler
- Condition builder with visual logic

#### 3. Field Configuration
- Add/remove/reorder fields
- Select field type from dropdown
- Configure validation with checkboxes
- Set default values with form inputs

#### 4. Condition Builder
- Visual if/then logic builder
- Expression builder for conditions
- Template variable picker
- Test conditions with sample data

### Technical Implementation

**Backend**:
```php
// Store/update drivers via API
DriverController::store(Request $request)
DriverController::update($name, Request $request)

// Convert visual config to YAML
DriverGeneratorService::toYaml(array $config): string
```

**Frontend**:
```vue
<!-- Main builder component -->
<FlowBuilder v-model="flowConfig">
  <template #palette>
    <HandlerPalette />
  </template>
  
  <template #canvas>
    <FlowCanvas @add-step="addStep" />
  </template>
  
  <template #inspector>
    <StepInspector :step="selectedStep" />
  </template>
</FlowBuilder>

<!-- Handler-specific forms -->
<FormHandlerConfig v-if="handler === 'form'" />
<LocationHandlerConfig v-if="handler === 'location'" />
```

**Libraries**:
- `@vue-flow/core` - Flow diagram builder
- `@formkit/vue` - Dynamic form builder
- `@vueuse/core` - Utilities

---

## Phase 4: Template Variable Assistant

**Goal**: Make template syntax accessible to non-developers.

### Features

#### 1. Variable Picker
- Browse available variables from source DTO
- Click to insert into template
- Preview variable value with sample data
- Show variable path (dot notation)

#### 2. Expression Builder
- Build expressions without syntax
- Visual logic builder (AND/OR/NOT)
- Comparison operators as buttons
- Filter selection (default, format_money, etc.)

#### 3. Live Template Preview
- Sample data input
- Real-time template rendering
- See what users will see
- Test different data scenarios

### UI Mockup

```
┌─────────────────────────────────────────────┐
│ Title                                        │
│ ┌─────────────────────────────────────────┐ │
│ │ Redeeming voucher {{ code }}            │ │
│ └─────────────────────────────────────────┘ │
│                                              │
│ Available Variables:                         │
│ ├─ {{ code }}            ABC123             │
│ ├─ {{ amount }}          500                │
│ ├─ {{ owner_name }}      John Doe           │
│ ├─ {{ source.cash.currency }} PHP           │
│ └─ [Browse all...]                           │
│                                              │
│ Preview with sample data:                    │
│ ┌─────────────────────────────────────────┐ │
│ │ Redeeming voucher ABC123                │ │
│ └─────────────────────────────────────────┘ │
└─────────────────────────────────────────────┘
```

---

## Phase 5: Full Visual Builder

**Goal**: Complete no-code driver creation experience.

### Features

#### 1. Driver Wizard
- Step-by-step driver creation
- Select source DTO from dropdown
- Configure basic settings
- Add steps one by one

#### 2. Handler Library
- Gallery of available handlers
- Preview of each handler's UI
- Configuration templates
- Copy/paste handler configs

#### 3. Testing & Preview
- Test flow with sample data
- Step-through preview mode
- See what users will experience
- Validate before saving

#### 4. Version Management
- Save driver versions
- Compare versions (diff view)
- Rollback to previous versions
- Export/import drivers

### Workflow

```
1. Create Driver
   ↓
2. Configure Basics (name, source, target)
   ↓
3. Add Steps (drag handlers)
   ↓
4. Configure Each Step (forms, not YAML)
   ↓
5. Set Conditions (visual builder)
   ↓
6. Configure Callbacks
   ↓
7. Test with Sample Data
   ↓
8. Preview Generated YAML
   ↓
9. Save (generates YAML file)
```

---

## Technology Stack

### Backend
- **Laravel** - Core framework
- **Symfony YAML** - YAML parsing (already used)
- **JSON Schema** - Validation
- **Spatie Laravel Data** - DTOs (already used)

### Frontend
- **Vue 3** + **Inertia.js** (already used)
- **Monaco Editor** - Code editor
- **Vue Flow** - Flow diagrams
- **FormKit** - Dynamic forms
- **Tailwind CSS** - Styling (already used)
- **shadcn-vue** - UI components (already used)

### Additional Libraries
- `js-yaml` - YAML parsing in browser
- `ajv` - JSON Schema validation
- `diff` - Version comparison
- `file-saver` - Export functionality

---

## Database Schema

```sql
-- Store driver versions and metadata
CREATE TABLE form_flow_drivers (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    version VARCHAR(20) NOT NULL,
    source VARCHAR(255) NOT NULL,
    target VARCHAR(255) NOT NULL,
    yaml_content TEXT NOT NULL,
    visual_config JSON,  -- UI builder state
    is_active BOOLEAN DEFAULT TRUE,
    is_example BOOLEAN DEFAULT FALSE,
    created_by BIGINT,
    updated_by BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX idx_source (source),
    INDEX idx_active (is_active)
);

-- Store driver versions for rollback
CREATE TABLE form_flow_driver_versions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    driver_id BIGINT NOT NULL,
    version VARCHAR(20) NOT NULL,
    yaml_content TEXT NOT NULL,
    visual_config JSON,
    change_summary TEXT,
    created_by BIGINT,
    created_at TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES form_flow_drivers(id) ON DELETE CASCADE
);

-- Store test data for driver previews
CREATE TABLE form_flow_driver_test_data (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    driver_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    sample_data JSON NOT NULL,
    created_at TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES form_flow_drivers(id) ON DELETE CASCADE
);
```

---

## Implementation Phases Timeline

### Phase 1: Read-Only Viewer (2-3 weeks)
- Week 1: Backend (list, show, validate endpoints)
- Week 2: Frontend (list view, flow diagram)
- Week 3: Testing & polish

### Phase 2: YAML Editor (2-3 weeks)
- Week 1: Monaco integration, schema setup
- Week 2: Autocomplete, validation
- Week 3: Live preview, testing

### Phase 3: Visual Builder (4-6 weeks)
- Week 1-2: Flow canvas, drag-drop
- Week 3-4: Step configuration panels
- Week 5-6: Field builder, testing

### Phase 4: Template Assistant (2-3 weeks)
- Week 1: Variable picker, expression builder
- Week 2: Live preview with sample data
- Week 3: Testing, UX refinement

### Phase 5: Full Builder (3-4 weeks)
- Week 1-2: Driver wizard, handler library
- Week 3: Version management, import/export
- Week 4: Testing, documentation

**Total Estimated Time**: 13-19 weeks (3-5 months)

---

## User Roles & Permissions

### Admin
- Create/edit/delete drivers
- Access all drivers (active + examples)
- Manage driver versions
- Export/import drivers

### Developer
- Create/edit drivers they own
- View all drivers (read-only)
- Test drivers with sample data
- Access YAML editor

### Viewer
- View driver list
- See flow diagrams
- No editing capabilities

---

## Security Considerations

1. **YAML Injection Prevention**
   - Validate YAML structure before saving
   - Sanitize template expressions
   - Whitelist allowed template functions

2. **Access Control**
   - Role-based permissions
   - Owner-based driver access
   - Audit log for changes

3. **Validation**
   - Schema validation on save
   - Test drivers in sandbox before activation
   - Prevent source/target class injection

4. **File System**
   - Store YAML in database, sync to filesystem
   - Prevent directory traversal
   - Validate file names

---

## Migration Strategy

### From YAML Files to Database

```php
// Artisan command
php artisan form-flow:import-drivers

// Imports existing YAML files to database
// Keeps filesystem sync for backward compatibility
// Marks as "imported from file"
```

### Hybrid Mode (Transition Period)

- UI builder saves to **database**
- Filesystem sync keeps YAML files updated
- Driver discovery checks **both** sources
- Database takes precedence if conflict

### Future: Database-Only

- All drivers in database
- YAML export for version control
- Import YAML from version control
- Filesystem only for git tracking

---

## API Endpoints

```
# Drivers CRUD
GET    /api/form-flow/drivers          # List all
POST   /api/form-flow/drivers          # Create
GET    /api/form-flow/drivers/{name}   # Show
PUT    /api/form-flow/drivers/{name}   # Update
DELETE /api/form-flow/drivers/{name}   # Delete

# Validation
POST /api/form-flow/drivers/validate   # Validate YAML

# Schema
GET /api/form-flow/schema              # Get JSON schema
GET /api/form-flow/handlers            # List available handlers

# Testing
POST /api/form-flow/drivers/{name}/test  # Test with sample data

# Versions
GET  /api/form-flow/drivers/{name}/versions        # List versions
POST /api/form-flow/drivers/{name}/versions        # Create version
POST /api/form-flow/drivers/{name}/rollback/{ver} # Rollback

# Import/Export
POST /api/form-flow/drivers/import    # Import YAML
GET  /api/form-flow/drivers/{name}/export # Export YAML
```

---

## Success Metrics

1. **Adoption**: % of drivers created via UI vs. hand-coded
2. **Productivity**: Time to create driver (before/after)
3. **Errors**: Syntax errors in YAML (should decrease)
4. **User Satisfaction**: Survey scores from builder users
5. **Feature Usage**: Most-used builder features

---

## Future Enhancements

### 1. AI-Assisted Builder
- Natural language to driver conversion
- "Create a 3-step form collecting name, email, and phone"
- AI suggests optimal flow structure

### 2. Template Marketplace
- Share drivers publicly
- Import community templates
- Rate and review templates

### 3. Multi-Language Support
- Translate drivers for i18n
- Manage translations in UI
- Preview in different languages

### 4. Analytics Integration
- Track completion rates per step
- A/B test different flows
- Optimize based on user behavior

---

## Documentation Needs

1. **User Guide**: How to use the visual builder
2. **Video Tutorials**: Walkthrough of creating a driver
3. **API Documentation**: For developers building integrations
4. **Migration Guide**: Moving from YAML to UI builder
5. **Best Practices**: Design patterns for flows

---

## Questions to Resolve

1. **Versioning**: Git-based or database-based version control?
2. **Permissions**: Fine-grained (per-driver) or role-based?
3. **Performance**: Cache parsed drivers? How long?
4. **Backward Compatibility**: Support YAML-only mode forever?
5. **Multi-Tenancy**: Per-tenant drivers or shared?

---

## Next Steps

1. **Validate Concept**: Build Phase 1 prototype
2. **User Feedback**: Show to potential users
3. **Prioritize Features**: Which phases deliver most value?
4. **Resource Allocation**: Assign developers, designers
5. **Set Timeline**: Realistic schedule with milestones

---

## Conclusion

The Form Flow UI Builder will democratize form flow creation, making it accessible to non-developers while maintaining the power and flexibility that developers need through YAML.

**Key Principle**: The UI generates what a developer would write by hand - no magic, just automation of tedious work.

**Long-term Vision**: A visual form builder that rivals TypeForm and Google Forms but with the power of Laravel's ecosystem behind it.
