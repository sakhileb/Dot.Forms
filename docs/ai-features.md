# AI Features Guide

This document explains the AI-powered features in Dot.Forms and how to use them.

## 1. AI Form Builder

Route: teams/{team}/forms/ai-builder

How it works:
- Enter a plain-language prompt describing your form.
- The system calls the AI form generator service.
- You can review and edit generated title/description/fields.
- Save to create a draft form.

Notes:
- If OPENAI_API_KEY is configured, Dot.Forms calls OpenAI.
- If no API key or request fails, Dot.Forms uses a deterministic fallback generator.

## 2. AI Field Suggestions

Route: teams/{team}/forms/{form}/ai-suggestions

How it works:
- Dot.Forms suggests fields based on form title and description.
- Select suggestions and apply to append them to your form.
- Use "Enhance Field Labels" to normalize labels (snake_case or camelCase to Title Case).

## 3. Smart Validation and Logic

Inside Form Builder:
- "AI Suggest Validation" adjusts field type/required state from label context.
- In field settings modal, "AI Suggest Logic" proposes conditional logic text based on nearby fields.

## 4. AI Submission Analytics

Route: teams/{team}/forms/{form}/ai-analytics

How it works:
- Click "Summarize 100 Submissions".
- Dot.Forms analyzes up to 100 recent submissions.
- Outputs:
  - Summary text
  - Sentiment counts (positive/neutral/negative)
  - Recommendations for improving completion quality

## 5. AI Queue Configuration

AI requests are represented as queue jobs and configured via:
- DOTFORMS_QUEUE_AI (default: ai)

Current UX uses sync dispatch for immediate feedback while still using queue-aware job classes.
