# Create one CSV file with all specific player entries
import csv


SOURCE_PREFIX = '_High Voltage SID Collection/'


def extract_parentheses(line):
	"""Return the text inside the first pair of parentheses."""
	start = line.find('(')
	end = line.find(')', start + 1)

	if start == -1 or end == -1:
		return None

	return line[start + 1:end]


def build_player_path(parentheses, rule):
	"""Return either a fixed output or a path based on the matched text."""

	if 'output' in rule:
		return rule['output']

	value = parentheses

	remove_prefix = rule.get('remove_prefix', '')
	if remove_prefix and value.startswith(remove_prefix):
		value = value[len(remove_prefix):]

	return (
		rule.get('output_prefix', '') +
		value +
		rule.get('output_suffix', '')
	)


if __name__ == '__main__':

	player_rules = [
		{
			'trigger': '(MoN/',
			'remove_prefix': 'MoN/',
			'output_prefix': 'MoN/FutureComposer/'
		},
		{
			'trigger': '(BeatBox',
			'output_prefix': 'SoundMonitor/'
		},
		{
			'trigger': '(JCH_NewPlayer_'
		},
		{
			'trigger': '(Dane_NewPlayer',
			'output_prefix': 'JCH/'
		},
		{
			'trigger': '(DigiMonitor'
		},
		{
			'trigger': '(Digitronix',
			'output_prefix': 'SoundMonitor/'
		},
		{
			'trigger': '(DMC_'
		},
		{
			'trigger': '(DrumMaker',
			'output_prefix': 'SoundMonitor/'
		},
		{
			'trigger': '(DUSAT'
		},
        {
            'trigger': '(FC_V3.x',
            'output': 'FutureComposer_V3.x'
        },
		{
			'trigger': '(FC_V4_Packed',
            'output': 'FutureComposer_V4_Packed'
		},
		{
			'trigger': '(FutureComposer_V1.0'
		},
		{
			'trigger': '(John_Player_'
		},
		{
			'trigger': '(Karl_XII',
			'output_prefix': 'SoundMonitor/'
		},
		{
			'trigger': '(VoiceTracker)',
			'output_prefix': 'Music_Assembler/'
		},
		{
			'trigger': '(Ten_Tracker)',
			'output_prefix': 'Music_Assembler/'
		},
		{
			'trigger': '(DoubleTracker)',
			'output_prefix': 'Music_Assembler/'
		},
		{
			'trigger': '(Music_Mixer)',
			'output_prefix': 'Music_Assembler/'
		},
		{
			'trigger': '(Music_Assembler/MC)',
			'output': 'Music_Assembler/MC'
		},
		{
			'trigger': '(MusicComposer',
			'output_suffix': '/Flash_Inc'
		},
		{
			'trigger': '(MusicMaster_',
			'output_prefix': 'SoundMonitor/'
		},
		{
			'trigger': '(ReD_Packed',
			'output_prefix': 'SoundMonitor/'
		},
		{
			'trigger': '(Rob_Hubbard_Digi'
		},
		{
			'trigger': '(SidWizard_'
		},
		{
			'trigger': '(Soundmaster_',
			'output_prefix': 'SoedeSoft/'
		},
		{
			'trigger': '(SoundMaker_'
		},
		{
			'trigger': '(Syndicate',
			'output_prefix': 'SoundMonitor/'
		},
		{
			'trigger': '(Ian_Crabtree',
			'output_prefix': 'Ariston/'
		},
		{
			'trigger': '(Wally_Beben',
			'output_prefix': 'Ariston/'
		},
		{
			'trigger': '(Audial_Arts_Digi)'
		},
		{
			'trigger': '(CheeseCutter_'
		},
		{
			'trigger': '(Sid_Sequencer)',
			'output': 'Companion/Sid_Sequencer'
		},
		{
			'trigger': '(Aleatory_Composer)',
			'output': 'Companion/Aleatory_Composer'
		},
		{
			'trigger': '(Companion/Murray)'
		},
		{
			'trigger': '(EMS_'
		}
	]

	with open('_specific.csv', 'r', encoding='utf-8') as file:
		content = file.readlines()

	with open('specific_players.csv', 'w', newline='', encoding='utf-8') as csvfile:
		writer = csv.writer(csvfile)

		last_sid_line = None

		for line in content:

			# Remember the latest line containing a SID filename
			if '.sid' in line:
				last_sid_line = line

			if not last_sid_line:
				continue

			for rule in player_rules:
				if rule['trigger'] not in line:
					continue

				parentheses = extract_parentheses(line)

				if parentheses is None:
					print(f'Could not parse player entry: {line.rstrip()}')
					continue

				sid_path = last_sid_line.partition('.sid')[0] + '.sid'
				player_path = build_player_path(parentheses, rule)

				writer.writerow([
					SOURCE_PREFIX + sid_path,
					player_path
				])