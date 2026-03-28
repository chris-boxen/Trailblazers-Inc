# ARCHITECTURE

## Purpose
This document describes the conceptual architecture of the Trailblazers website and data model.

## Core concepts

### Family
A household/account context.
- Owns contact/account context
- Connects to athletes
- Can persist across many seasons

### Athlete
A person/participant record.
- Belongs to a family
- Can participate across multiple seasons
- Can have results, records, physicals, and enrollments

### Coach
A coach/personnel record.
- Used for staff/season display
- Can be associated with seasons

### Athletic Season
A seasonal hub.
- Represents a season such as cross country or track & field
- Connects conceptually to applications, enrollments, meets, and coaches

### Application
A family-season submission.
- Represents what a household submitted for a season
- May cover multiple athlete enrollments

### Enrollment
The athlete-season operational record.
- Connects Athlete + Family + Season + Application
- Stores operational status for payment, physical, uniforms, and participation

### Athletic Physical
A compliance/medical record.
- Belongs to an athlete
- Can span more than one season
- May be linked from enrollment as the physical used for clearance

### Athletic Event
The canonical definition of an event.
- Defines the type of event
- Provides structural event metadata

### Athletic Meet
A specific meet instance.
- Date, location, and meet-level context
- Contains results across many athletes and events

### Athletic Result
An athlete’s performance in an event at a meet.
- Junction of Athlete + Event + Meet
- Stores performance data

### Athletic Record
A stored achievement record linked to a source result.
- Used for PR/SR or other promoted achievements

## Architectural rules
1. Prefer one source of truth over mirrored fields.
2. Use Enrollment for athlete-season operations.
3. Keep Physical separate from Enrollment.
4. Keep Result as the performance-level junction object.
5. Use Record as a lightweight stored achievement layer linked back to Result.
6. Avoid redundant reverse relationships when they can be queried.