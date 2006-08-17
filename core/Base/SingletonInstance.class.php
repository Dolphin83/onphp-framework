<?php
/***************************************************************************
 *   Copyright (C) 2004-2006 by Sveta Smirnova                             *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 ***************************************************************************/
/* $Id$ */

	/**
	 * @deprecated by Occam's Razor
	 * @ingroup Base
	**/
	final class SingletonInstance extends Singleton
	{
		public function __call($class, $args = null)
		{
			return call_user_func_array(
				array('Singleton', 'getInstance'),
				$args
			);
		}
	}
?>