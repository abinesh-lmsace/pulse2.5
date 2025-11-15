# Pulse Credits Action Plugin - Implementation Plan

## Project Overview
Implement automatic credit allocation system for Pulse module using the automation engine as a new action plugin (`pulseaction_credits`).

## Requirements Summary
1. **Automatic allocation of credits** - Rule-based credit distribution using Pulse automation engine
2. **Override allocation of credits** - Manual adjustment interface for specific users
3. **Display credits in navigation bar** - Prominent credit display (future implementation)

## Moodle Coding Standards
- **Variables**: lowercase without underscores (`$creditamount` not `$credit_amount`)
- **Classes**: lowercase (`class actionform` not `class ActionForm`)
- **Constants**: UPPERCASE with underscores (`const ALLOCATION_ADD = 1`)
- **Methods**: lowercase (`public function config_shortname()`)
- **Namespaces**: follow existing Pulse patterns

## File Structure Plan

### ✅ Core Plugin Structure: `actions/credits/` - IMPLEMENTED
```
actions/credits/
├── version.php                     # ✅ Plugin metadata
├── lib.php                        # ✅ Required functions
├── access.php                     # ✅ Capability definitions
├── classes/
│   ├── actionform.php             # ✅ Main action form (extends action_base)
│   ├── credits.php                # ✅ Core credits management (Phase 2)
│   ├── manager.php                # ✅ Allocation manager (Phase 2)
│   ├── schedule.php               # ✅ Scheduling logic (Phase 2)
│   ├── helper.php                 # ⏳ Utility functions
│   ├── external.php               # ⏳ Web services
│   ├── task/
│   │   └── allocatecredits.php    # ⏳ Adhoc task for allocation
│   ├── reportbuilder/
│   │   └── datasource/
│   │       └── credits.php        # ✅ Report data source (Phase 3)
│   └── local/
│       ├── credits.php            # ✅ Core credits class (Phase 2)
│       ├── credits_schedule.php   # ✅ Scheduling implementation (Phase 2)
│       └── entities/
│           └── creditallocation.php # ✅ Report entity (Phase 3)
├── db/
│   ├── install.xml                # ✅ Database schema
│   ├── upgrade.php                # ⏳ DB upgrades
│   ├── tasks.php                  # ⏳ Task definitions
│   ├── services.php               # ⏳ Web services
│   └── access.php                 # ✅ Capabilities
├── lang/en/
│   └── pulseaction_credits.php    # ✅ Language strings (including report strings)
├── backup/moodle2/                # ⏳ Backup/restore support
├── tests/                         # ⏳ Unit tests and generators
└── templates/                     # ⏳ Mustache templates
```

**Legend:** ✅ Completed | ⏳ Pending Implementation

## Database Schema

### ✅ New Table: `pulseaction_credits_sch` - IMPLEMENTED
Tracks individual credit allocations per user:
- `id` - Primary key
- `instanceid` - Pulse automation instance ID
- `userid` - Target user ID
- `courseid` - Course context
- `creditamount` - Credits to allocate
- `allocationmethod` - 1=add, 2=replace
- `scheduledtime` - When to allocate
- `status` - 1=planned, 2=allocated, 3=failed
- `timecreated`, `timemodified`, `completedtime`
- `errorlog` - Error details if failed

### Additional Tables Created

**`pulseaction_credits`** - Template configurations:
- `id`, `templateid` (unique key)
- `creditstatus`, `creditamount`, `allocationmethod`
- `intervaltype`, `intervalconfig` (JSON)
- `basedatetype`, `basedateconfig` (JSON)
- `recipients` (JSON array)
- `timecreated`, `timemodified`

**`pulseaction_credits_ins`** - Instance configurations:
- Same structure as template table but linked to `instanceid`
- Allows per-course overrides of template settings

## Key Implementation Classes

### 1. `actionform` class ✅ IMPLEMENTED
- Extends `\mod_pulse\automation\action_base`
- Implements configuration form with conditional fields
- Handles form validation and data processing
- Method: `config_shortname()` returns `'pulsecredits'`
- Form elements: status, credit amount, allocation method, intervals, base date, recipients
- Integration with Pulse automation triggers and event handling

### 2. `credits` class
- Core credit management functionality
- Integration with user profile fields
- Credit calculation logic (add vs replace)
- Error handling and logging

### 3. `manager` class
- Schedules credit allocations based on automation triggers
- Manages batch processing of users
- Handles recurring allocations
- Creates adhoc tasks for actual allocation

### 4. `schedule` class
- Calculates next allocation times based on intervals
- Handles relative vs fixed base dates
- Supports complex scheduling (yearly, custom crontab)
- Manages timezone considerations

### 5. Report Builder Integration
- Custom datasource for credit allocations
- Entity for credit allocation fields
- Support for filtering and column customization
- Embedded report for override functionality

## Form Configuration Fields

Following requirements specification:
- **Status** - Enable/Disable dropdown
- **Credit amount** - Number input (hidden when disabled)
- **Allocation method** - Add credits vs Replace credits
- **Interval** - Once/Daily/Weekly/Monthly/Yearly/Custom crontab
- **Base date** - Relative (enrollment) vs Fixed date
- **Recipients** - Multi-select role picker

## Integration Points

### With Existing Pulse System
- Uses existing automation engine infrastructure
- Leverages existing condition plugins (cohort, course completion, etc.)
- Integrates with existing queueing system
- Follows established plugin architecture

### With Credit System
- Reads credit profile field from existing addon settings
- Updates user profile field values
- Maintains compatibility with `enrol_credit` and `availability_credit`

### With Report Builder
- Creates custom report source for monitoring
- Supports embedded reports for override functionality
- Provides comprehensive filtering and display options

## Development Phases

### ✅ Phase 1: Core Structure - COMPLETED
1. ✅ Create plugin directory structure
2. ✅ Implement basic `actionform` class
3. ✅ Set up database schema and installation
4. ✅ Create language strings

**Implementation Summary:**
- Complete directory structure created following Pulse plugin patterns
- Main `actionform.php` class implemented with all required methods
- Database schema with 3 tables: `pulseaction_credits`, `pulseaction_credits_ins`, `pulseaction_credits_sch`
- Capability definitions for credit management
- Complete language strings for form elements and messages
- Form configuration with all required fields (status, amount, method, interval, base date, recipients)

### ✅ Phase 2: Core Functionality - COMPLETED
1. ✅ Implemented `credits` and `manager` classes
2. ✅ Created scheduling logic (`credits_schedule.php`)
3. ✅ Developed credit allocation functionality
4. ✅ Added comprehensive error handling

**Implementation Summary:**
- Complete `credits.php` class with allocation logic and profile field integration
- Advanced `credits_schedule.php` class with full scheduling capabilities
- Credit allocation supports both ADD and REPLACE methods
- Integration with user profile fields for credit storage
- Comprehensive error handling and logging capabilities

### ✅ Phase 3: Report Builder Integration - COMPLETED
1. ✅ Implemented report builder datasource and entity classes
2. ✅ Created comprehensive reporting capabilities
3. ✅ Added filtering and column customization options
4. ✅ Language strings for all report elements

**Implementation Summary:**
- Complete `reportbuilder/datasource/credits.php` datasource class
- Comprehensive `local/entities/creditallocation.php` entity class
- Support for all required report fields per specification:
  - Course object (full name, short name, start date, etc.)
  - Automation (title, reference, visibility, internal notes, etc.)
  - Conditions support
  - Credits (status, amount, allocation method, interval, base date, recipients)
  - User object (firstname, lastname, email, etc.)
  - Credit allocations (allocation date/time, status: planned/allocated/failed)
- Advanced filtering capabilities including cohort-based filters
- Proper database schema alignment and field mapping

### Phase 4: Override Interface Implementation - NEXT

#### Simplified Database Schema
**Single Table Required:**

**`pulseaction_credits_override`** - Override Records
- `id`, `scheduleid` (FK), `userid`, `courseid`
- `overrideamount`, `overriddenby`, `timecreated`, `status`

*Note: No separate logging table - use Moodle custom events for audit trail*

#### Implementation Structure
```
classes/
├── systemreports/
│   └── schedule.php             # System report for schedules (embedded)
├── event/
│   ├── credit_overridden.php    # Custom event for overrides
│   └── credit_allocated.php     # Custom event for allocations
├── output/
│   └── override_amount.php      # Editable override amount component
└── local/
    └── override_manager.php     # Override logic and validation
pages/
└── override.php                 # Override interface page
```

#### Key Implementation Approach
1. **System Report**: Extend `\core_reportbuilder\system_report` for embedded table
2. **Custom Events**: Extend `\core\event\base` for automatic Moodle logging
3. **Inline Editing**: Use Moodle's editable output components (like tag system)
4. **Page Integration**: Simple PHP page using `system_report_factory::create()`

#### Features to Implement
1. ✅ System report with schedule data and inline editing
2. ✅ Custom events for audit trail in standard Moodle logs
3. ✅ Override management page in pulse credits plugin
4. ✅ Editable components for override amounts
5. ✅ Bulk override operations with checkboxes

**Implementation Summary:**
- Database table `pulseaction_credits_override` with upgrade script
- System report `systemreports/schedule.php` with embedded display
- Custom events: `credit_overridden` and `credit_allocated` for audit trail
- Override manager with validation and conflict prevention
- Override interface page at `pages/override.php` with bulk operations
- Inline editable component for override amounts with AJAX updates
- Web services for real-time editing functionality
- Complete language strings and capability system
- Statistics display and user permission handling

### Phase 5: Testing & Quality Assurance - NEXT
1. Add comprehensive testing
2. Implement backup/restore support

### Phase 4: Integration & Testing
1. Integration testing with existing Pulse features
2. Performance testing with large user sets
3. UI/UX refinements
4. Documentation completion

## Testing Strategy
- Unit tests for all core classes
- Integration tests with Pulse automation engine
- Behat tests for form functionality
- Performance tests for batch processing
- Test data generators for various scenarios

## Migration Considerations
- Ensure compatibility with existing credit addon
- Provide migration path for existing configurations
- Maintain backward compatibility where possible
- Clear upgrade documentation

## Future Enhancements
- Navigation bar credit display (requirement #3)
- Advanced scheduling options
- Credit transaction history
- Integration with other Moodle systems
- Mobile app support

---

## Development Commands

### Testing
```bash
# Run unit tests
php admin/tool/phpunit/cli/util.php --install
php vendor/bin/phpunit mod/pulse/actions/credits/tests/

# Run Behat tests
php admin/tool/behat/cli/init.php
php vendor/bin/behat --tags=@pulseaction_credits
```

### Code Quality
```bash
# PHP Code Sniffer
php local/codechecker/phpcs/bin/phpcs --standard=moodle mod/pulse/actions/credits/

# PHP Mess Detector
php local/codechecker/phpmd/bin/phpmd mod/pulse/actions/credits/ text codesize,controversial,design,naming,unusedcode
```

This implementation leverages the existing Pulse automation architecture while providing comprehensive credit allocation functionality as specified in the requirements.