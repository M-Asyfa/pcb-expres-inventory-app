# Brand Guidelines - PCB Jogja SCADA Inventory

## Brand Identity
**Name:** PCB Expres Jogja - SCADA Platform Inventory
**Tagline:** Satu tempat untuk memantau perangkat, alert, dan kondisi lapangan.
**Personality:** Industrial, precise, trustworthy, technical, calm operations.

## Voice
- Technical but human, Bahasa Indonesia + English mixed for ops
- Short, direct, no jargon overload
- Action-oriented: "Monitoring", "Ringkasan cepat", "Prioritas kejadian"

## Visual Identity - From Reference Screenshot
### Colors (Primitive)
- **SCADA Navy** #0F1E35 (primary, sidebar dark card, buttons)
- **Beige Scale** #FFFBF2 (50), #FAF6EE (100 bg), #F6EFE0 (200 sidebar), #F3EBD9 (300), #E8DDC7 (400 border), #F0E6D2 (500 border), #DCCEB0 (600)
- **Slate** #0F172A foreground, #64748B muted
- **Pills**: Region white + beige border, Role blue #DBEAFE/#1E40AF, Live green #D1FAE5/#065F46
- **Blobs**: Mint #A7F3D0, Blue #BFDBFE, Amber #FDE68A (blurred 30px)
- **Semantic**: Success #059669, Warning #F59E0B, Destructive #DC2626

### Typography
- **Sans**: Inter (400,500,600,700,800,900) - body, UI, headings
- **Mono**: Fira Code (400,500,600) - IDs, Box-Laci, code, technical data
- Scale: 2xs 10px (labels), xs 11-12px (descriptions), sm 13-14px (body), base 16px, 4xl 40px (dashboard numbers), 5xl 56px (big stats)
- Tracking: [0.18em]-[0.2em] for overlines like MONITORING OVERVIEW, OPERATOR WORKSPACE

### Radius & Shadows
- Card: 20px, Stat: 22px, Pill: full, Input: 12px
- Shadows: card 0 4px 20px rgba(0,0,0,0.04), dark card 0 8px 24px rgba(15,30,53,0.25)

### Layout
- Sidebar 280px, bg beige 200/60, border E8DDC7
- Header white rounded 18px, border F0E6D2
- Page padding 24px, gap 20px
- Cards white, border F0E6D2, overflow hidden for blobs

## Components
- **Sidebar dark card**: SCADA PLATFORM overline 10px white/60, PCB Jogja 22px extrabold, description 12px white/70
- **Nav item**: rounded 14px, px 4 py 3, active bg white shadow 0 4px 16px rgba(0,0,0,0.06) border E8DDC7, inactive hover white/60, title 13px bold, subtitle 11px gray
- **Stat card**: relative overflow-hidden, blob absolute -top-8 -right-8 w-32 h-32 blur 30px opacity 50, overline 10px tracking, number 56px black 900, desc 12px gray max-w 240-260
- **Alert card**: title 15px bold, subtitle 11px gray, badge red 50 border red 100, list items bg FFFBF2 border F0E6D2 rounded-xl

## Imagery
- Map placeholder: Java island map with city labels (Serang, Jakarta, Bekasi, etc) - using simple grid of Box cards as abstract map
- No heavy imagery, keep minimal, industrial

## Tokens
See `src/design-tokens.json` and `src/design-tokens.css` - three layer: primitive -> semantic -> component
