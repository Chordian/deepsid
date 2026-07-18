import csv
import re


SOURCE_PREFIX = '_High Voltage SID Collection/'


PLAYER_RULES = {
	'JCH_NewPlayer': {
		'comparison_names': ['NewPlayer']
	},
	'MusicComposer/FlashInc': {
		'comparison_names': ['MusicComposer']
	},
	'Hermit/SidWizard_V1.x': {
		'comparison_names': ['SidWizard']
	},
	'CheeseCutter_2.x': {
		'comparison_names': ['CheeseCutter']
	},
	'EMS/Odie': {
		'comparison_names': ['EMS']
	},
	'Blackbird/LFT': {
		'comparison_names': ['Blackbird']
	},
    # Now handled in 'pretty_player_names.php'
    # @todo CHECK THAT IT WORKS IN HVSC UPDATE #86!
    #    
	#'MoN/FutureComposer': {
	#	'comparison_names': ['FutureComposer', 'MoN/']
	#},
	'Music_Assembler': {
		'comparison_names': ['Music_Assembler/MC']
	},
	'Rob_Hubbard': {
		'comparison_names': ['Rob_Hubbard_Digi']
	},
	'GoatTracker_V1.x': {
		'replacements': {
			'GT_': 'GoatTracker_'
		}
	},
	'GoatTracker_V2.x': {
		'replacements': {
			'GT_': 'GoatTracker_'
		}
	}
}


def is_hex_line(line):
	"""
	Return True when a line appears to contain SID identification bytes.

	Examples:
		A9 ?? 8D ?? D4
		BD ?? ?? 85 02
		A2 02 ... && B1 ?? ...
	"""
	parts = line.strip().split()

	if not parts:
		return False

	valid_parts = 0

	for part in parts:
		if part == '&&':
			continue

		if part == '??' or re.fullmatch(r'[0-9A-Fa-f]{2}', part):
			valid_parts += 1
		else:
			return False

	return valid_parts > 0


def remove_version(name):
	"""
	Reduce a player name to a useful comparison name.

	Examples:
		CheeseCutter_2.x       -> CheeseCutter
		SidWizard_V1.x         -> SidWizard
		JCH_NewPlayer          -> JCH_NewPlayer
		MusicComposer          -> MusicComposer
	"""
	return re.sub(
		r'_(?:V)?(?:\d[\w.+?*-]*|[Vv]\d[\w.+?*-]*)$',
		'',
		name
	)


def get_comparison_names(parent_name):
	rule = PLAYER_RULES.get(parent_name, {})

	if 'comparison_names' in rule:
		return rule['comparison_names']

	base_name = parent_name.rsplit('/', 1)[-1]
	return [remove_version(base_name)]


def build_player_path(parent_name, child_name):
	"""
	Build the final player path.

	If the child already contains the relevant parent player name,
	return only the child name to avoid duplication.

	Otherwise prefix it with the complete parent name.
	"""
	rule = PLAYER_RULES.get(parent_name, {})

	# Apply child-name replacements first
	for old, new in rule.get('replacements', {}).items():
		if child_name.startswith(old):
			child_name = new + child_name[len(old):]
			break

	comparison_names = get_comparison_names(parent_name)
	child_lower = child_name.lower()

	if any(name.lower() in child_lower for name in comparison_names):
		return child_name

	return parent_name + '/' + child_name


def parse_sidid_cfg(filename):
	"""
	Read sidid.cfg and return mappings such as:

		Ian_Crabtree_V1       -> Ariston/Ian_Crabtree_V1
		CheeseCutter_2.3-2.4  -> CheeseCutter_2.3-2.4
		SidWizard_V1.2        -> SidWizard_V1.2
		Dane_NewPlayer        -> Dane_NewPlayer
	"""
	player_identifiers = {}

	current_parent = None

	with open(filename, 'r', encoding='utf-8') as file:
		for raw_line in file:
			line = raw_line.strip()

			if not line:
				continue

			# A parenthesized identifier belongs to the latest parent player.
			if line.startswith('(') and line.endswith(')'):
				if current_parent is None:
					print(f'Identifier without parent: {line}')
					continue

				child_name = line[1:-1]
				player_identifiers[child_name] = build_player_path(
					current_parent,
					child_name
				)

				continue

			# Ignore hexadecimal identification lines.
			if is_hex_line(line):
				continue

			# Any other text line starts a new player block.
			current_parent = line

	return player_identifiers


def extract_parenthesized_name(line):
	start = line.find('(')
	end = line.find(')', start + 1)

	if start == -1 or end == -1:
		return None

	return line[start + 1:end]


def create_csv(specific_filename, sidid_filename, output_filename):
	player_identifiers = parse_sidid_cfg(sidid_filename)

	with open(specific_filename, 'r', encoding='utf-8') as file:
		content = file.readlines()

	with open(output_filename, 'w', newline='', encoding='utf-8') as csvfile:
		writer = csv.writer(csvfile)

		last_sid_line = None

		for line in content:
			if '.sid' in line:
				last_sid_line = line

			if not last_sid_line or '(' not in line:
				continue

			identifier = extract_parenthesized_name(line)

			if not identifier:
				continue

			if identifier not in player_identifiers:
				continue

			sid_path = last_sid_line.partition('.sid')[0] + '.sid'
			player_path = player_identifiers[identifier]

			writer.writerow([
				SOURCE_PREFIX + sid_path,
				player_path
			])


if __name__ == '__main__':
	create_csv(
		'_specific.csv',
		'sidid.cfg',
		'specific_players.csv'
	)